<?php

use App\Actions\SeedWeddingChecklist;
use App\Enums\WeddingRole;
use App\Models\Category;
use App\Models\ChecklistItem;
use App\Models\ChecklistSection;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingTask;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ChecklistSeeder;

beforeEach(function () {
    $this->seed([CategorySeeder::class, ChecklistSeeder::class]);
    $this->aina = User::factory()->create();
    $this->wedding = Wedding::factory()->for($this->aina)->create(['event_date' => now()->addYear()->toDateString()]);
});

it('fills a new wedding with the starter checklist and a budget plan', function () {
    $this->actingAs($this->aina)
        ->post(route('weddings.store'), [
            'title' => 'Aina & Hakim',
            'event_date' => now()->addYear()->toDateString(),
            'city' => 'Alor Setar',
            'state' => 'Kedah',
            'budget' => 30000,
        ])
        ->assertRedirect(route('dashboard'));

    $wedding = Wedding::latest('id')->first();

    expect($wedding->tasks)->toHaveCount(ChecklistItem::active()->count())
        ->and($wedding->budgetItems)->toHaveCount(Category::active()->count())
        ->and((float) $wedding->budgetItems()->sum('planned_amount'))->toBeGreaterThan(0);

    // A task with a deadline never lands before today; the ones after the akad have none.
    expect($wedding->tasks->every(fn ($task) => $task->due_date === null || $task->due_date->gte(today())))->toBeTrue()
        ->and($wedding->tasks->whereNotNull('due_date'))->not->toBeEmpty();
});

it('keeps a short-notice wedding from having overdue tasks on day one', function () {
    app(SeedWeddingChecklist::class)->handle(
        Wedding::factory()->for($this->aina)->create(['event_date' => now()->addWeeks(2)->toDateString()])
    );

    expect(WeddingTask::query()->whereDate('due_date', '<', today())->count())->toBe(0);
});

it('ticks a handful of tasks in one save, and records who did it', function () {
    app(SeedWeddingChecklist::class)->handle($this->wedding);
    $hakim = User::factory()->create();
    $this->wedding->addMember($hakim, WeddingRole::Partner);
    $tasks = $this->wedding->tasks()->take(3)->get();

    $this->actingAs($hakim)
        ->put(route('weddings.tasks.update', $this->wedding), ['done' => $tasks->pluck('id')->all()])
        ->assertRedirect()
        ->assertSessionHas('status', '3 tugasan dikemas kini.');

    expect($this->wedding->tasks()->whereNotNull('completed_at')->count())->toBe(3)
        ->and($this->wedding->tasks()->where('completed_by', $hakim->id)->count())->toBe(3);

    // Aina sees who ticked them, and unticks two of them in one go.
    $this->actingAs($this->aina)->get(route('checklist.index'))->assertOk()->assertSee('oleh '.$hakim->name);

    $this->actingAs($this->aina)
        ->put(route('weddings.tasks.update', $this->wedding), ['undone' => $tasks->take(2)->pluck('id')->all()])
        ->assertRedirect();

    expect($this->wedding->tasks()->whereNotNull('completed_at')->count())->toBe(1);
});

it('saves a tick and an untick together, and says when nothing changed', function () {
    app(SeedWeddingChecklist::class)->handle($this->wedding);
    [$ticked, $untouched] = $this->wedding->tasks()->take(2)->get()->all();
    $ticked->update(['completed_at' => now(), 'completed_by' => $this->aina->id]);

    $this->actingAs($this->aina)
        ->put(route('weddings.tasks.update', $this->wedding), [
            'done' => [$untouched->id],
            'undone' => [$ticked->id],
        ])
        ->assertRedirect()
        ->assertSessionHas('status', '2 tugasan dikemas kini.');

    expect($ticked->fresh()->isDone())->toBeFalse()
        ->and($untouched->fresh()->isDone())->toBeTrue();

    // Saving the same state again changes nothing and says so.
    $this->actingAs($this->aina)
        ->put(route('weddings.tasks.update', $this->wedding), ['done' => [$untouched->id]])
        ->assertRedirect()
        ->assertSessionHas('status', 'Tiada perubahan untuk disimpan.');
});

it('refuses to tick a task that belongs to another wedding', function () {
    app(SeedWeddingChecklist::class)->handle($this->wedding);
    $stranger = Wedding::factory()->for(User::factory()->create())->create();
    $theirs = $stranger->tasks()->create(['title' => 'Tugasan orang lain', 'sort_order' => 0]);

    $this->actingAs($this->aina)
        ->put(route('weddings.tasks.update', $this->wedding), ['done' => [$theirs->id]])
        ->assertRedirect();

    expect($theirs->fresh()->isDone())->toBeFalse();
});

it('adds and deletes a custom task', function () {
    $this->actingAs($this->aina)
        ->post(route('weddings.tasks.store', $this->wedding), ['title' => 'Tempah kereta pengantin', 'due_date' => now()->addMonths(2)->toDateString()])
        ->assertRedirect();

    $task = WeddingTask::sole();
    expect($task->title)->toBe('Tempah kereta pengantin');

    $this->actingAs($this->aina)->delete(route('weddings.tasks.destroy', [$this->wedding, $task]))->assertRedirect();
    expect(WeddingTask::count())->toBe(0);
});

it('flags an overdue task and keeps strangers out', function () {
    WeddingTask::factory()->overdue()->for($this->wedding)->create(['title' => 'Tempah pelamin']);

    $this->actingAs($this->aina)->get(route('checklist.index'))->assertOk()->assertSee('Lewat');

    $stranger = User::factory()->create();
    $this->actingAs($stranger)
        ->post(route('weddings.tasks.store', $this->wedding), ['title' => 'Tugasan penyusup'])
        ->assertForbidden();
});

it('groups the checklist into the phases admin set up, each with its own progress', function () {
    app(SeedWeddingChecklist::class)->handle($this->wedding);

    $this->actingAs($this->aina)
        ->get(route('checklist.index'))
        ->assertOk()
        ->assertViewHas('props', function (array $props): bool {
            $first = $props['sections'][0];
            $titles = collect($props['sections'])->pluck('title');

            return $titles->first() === ChecklistSection::ordered()->first()->title
                && $titles->count() === ChecklistSection::count()
                && $first['total'] === ChecklistSection::ordered()->first()->items()->count()
                && $first['done'] === 0
                && $first['percent'] === 0;
        });
});

it('files a task the couple wrote themselves under its own heading, last', function () {
    app(SeedWeddingChecklist::class)->handle($this->wedding);

    $this->actingAs($this->aina)->post(route('weddings.tasks.store', $this->wedding), ['title' => 'Tempah kereta pengantin']);

    $this->actingAs($this->aina)
        ->get(route('checklist.index'))
        ->assertOk()
        ->assertViewHas('props', function (array $props): bool {
            $last = collect($props['sections'])->last();

            return $last['title'] === 'Tugasan saya sendiri'
                && $last['groups'][0]['tasks'][0]['title'] === 'Tempah kereta pengantin';
        });
});

it('picks up a task admin added after the wedding was created, once only', function () {
    app(SeedWeddingChecklist::class)->handle($this->wedding);
    $before = $this->wedding->tasks()->count();

    ChecklistItem::factory()->create([
        'checklist_section_id' => ChecklistSection::ordered()->first()->id,
        'title' => 'Semak pakej dron',
    ]);

    $this->actingAs($this->aina)->get(route('checklist.index'))->assertOk();
    expect($this->wedding->tasks()->count())->toBe($before + 1);

    // A second visit must not add it again.
    $this->actingAs($this->aina)->get(route('checklist.index'))->assertOk();
    expect($this->wedding->tasks()->count())->toBe($before + 1)
        ->and($this->wedding->tasks()->where('title', 'Semak pakej dron')->exists())->toBeTrue();
});

it('leaves a couple task alone when the master item behind it is deleted', function () {
    app(SeedWeddingChecklist::class)->handle($this->wedding);

    $item = ChecklistItem::ordered()->first();
    $task = $this->wedding->tasks()->where('checklist_item_id', $item->id)->sole();
    $task->update(['completed_at' => now(), 'completed_by' => $this->aina->id]);

    $item->delete();

    $task->refresh();
    expect($task->exists)->toBeTrue()
        ->and($task->isDone())->toBeTrue()
        ->and($task->checklist_item_id)->toBeNull();
});

it('skips a phase or a task an admin switched off', function () {
    ChecklistSection::query()->update(['is_active' => false]);
    $section = ChecklistSection::factory()->create(['is_active' => true]);
    ChecklistItem::factory()->for($section, 'section')->create(['title' => 'Tugasan aktif']);
    ChecklistItem::factory()->for($section, 'section')->create(['title' => 'Tugasan tidak aktif', 'is_active' => false]);

    app(SeedWeddingChecklist::class)->handle($this->wedding);

    expect($this->wedding->tasks()->pluck('title')->all())->toBe(['Tugasan aktif']);
});

it('files a task the couple already had under its phase instead of adding it twice', function () {
    $item = ChecklistItem::whereTranslated('title', 'Tempah katering')->sole();

    $mine = $this->wedding->tasks()->create([
        'title' => 'Tempah katering',
        'completed_at' => now(),
        'completed_by' => $this->aina->id,
        'sort_order' => 0,
    ]);

    app(SeedWeddingChecklist::class)->handle($this->wedding);

    $mine->refresh();
    expect($this->wedding->tasks()->where('title', 'Tempah katering')->count())->toBe(1)
        ->and($mine->checklist_item_id)->toBe($item->id)
        ->and($mine->checklist_section_id)->toBe($item->checklist_section_id)
        ->and($mine->isDone())->toBeTrue();
});
