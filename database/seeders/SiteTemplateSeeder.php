<?php

namespace Database\Seeders;

use App\Models\SiteTemplate;
use App\Support\Card\Catalog;
use Illuminate\Database\Seeder;

class SiteTemplateSeeder extends Seeder
{
    /**
     * The layered invitation designs, composed from App\Support\Card\Catalog.
     *
     * A design is a palette of ten colour roles, four type faces and three canvases
     * of layers; the artwork is code, so re-running this is how a tweak to a design
     * reaches every card. Nothing a couple saved is touched — their colours, fonts,
     * photos and content live on their own row.
     */
    public function run(): void
    {
        foreach (Catalog::all() as $definition) {
            SiteTemplate::updateOrCreate(
                ['slug' => $definition['slug']],
                SiteTemplate::attributesFromDefinition($definition),
            );
        }

        // A design withdrawn from the catalogue stops being offered, but stays on the
        // row so a card already using it still renders.
        SiteTemplate::query()
            ->whereNotIn('slug', collect(Catalog::all())->pluck('slug'))
            ->update(['is_active' => false]);
    }
}
