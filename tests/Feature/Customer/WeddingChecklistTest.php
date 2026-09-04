<?php

use App\Actions\SeedWeddingChecklist;
use App\Enums\WeddingRole;
use App\Models\Category;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingTask;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
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

    expect($wedding->tasks)->toHaveCount(count(SeedWeddingChecklist::TEMPLATE))
        ->and($wedding->budgetItems)->toHaveCount(Category::active()->count())
        ->and((float) $wedding->budgetItems()->sum('planned_amount'))->toBeGreaterThan(0);

    // Every task gets a due date, and none of them lands before today.
    expect($wedding->tasks->every(fn ($task) => $task->due_date !== null && $task->due_date->gte(today())))->toBeTrue();
});

it('keeps a short-notice wedding from having overdue tasks on day one', function () {
    app(SeedWeddingChecklist::class)->handle(
        Wedding::factory()->for($this->aina)->create(['event_date' => now()->addWeeks(2)->toDateString()])
    );

    expect(WeddingTask::query()->whereDate('due_date', '<', today())->count())->toBe(0);
});

it('shows progress and lets either partner tick a task', function () {
    app(SeedWeddingChecklist::class)->handle($this->wedding);
    $hakim = User::factory()->create();
    $this->wedding->addMember($hakim, WeddingRole::Partner);
    $task = $this->wedding->tasks()->first();

    $this->actingAs($hakim)
        ->put(route('weddings.tasks.update', [$this->wedding, $task]), ['done' => 1])
        ->assertRedirect();

    $task->refresh();
    expect($task->isDone())->toBeTrue()->and($task->completed_by)->toBe($hakim->id);

    // Aina sees who ticked it.
    $this->actingAs($this->aina)->get(route('checklist.index'))->assertOk()->assertSee('oleh '.$hakim->name);

    $this->actingAs($this->aina)
        ->put(route('weddings.tasks.update', [$this->wedding, $task]), ['done' => 0])
        ->assertRedirect();

    expect($task->fresh()->isDone())->toBeFalse();
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
