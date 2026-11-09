<?php

namespace KraftInk\Plugins\Staff\Providers;

use App\Events\CollectingSeoPages;
use App\Kraft\Features;
use App\Kraft\Widgets\WidgetRegistry;
use App\Models\Accounts\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use KraftInk\Plugins\Staff\Listeners\DocumentRetention;
use KraftInk\Plugins\Staff\Models\Document;
use KraftInk\Plugins\Staff\Services\Directory;
use KraftInk\Plugins\Staff\Services\DocumentTrash;
use KraftInk\Plugins\Staff\Services\Imports\ImportMappings;
use App\Events\PruningData;
use App\Kraft\Media\MediaSelection;
use App\Kraft\Migration\MappingRegistry;
use App\Kraft\Trash\TrashRegistry;

class PluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(PruningData::class, [DocumentRetention::class, 'prune']);
        app(MediaSelection::class)->register('staff-image', 'staff.manage', 'image');
        $this->registerWidgets(app(WidgetRegistry::class));
        app(Features::class)->register(
            'staff',
            fn (?User $user) => Document::query()->visibleTo($user)->exists() ||
                app(Directory::class)->hasMembers($user),
        );
        app(ImportMappings::class)->register(app(MappingRegistry::class));
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'staff');
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'staff');
        app(DocumentTrash::class)->register(app(TrashRegistry::class));
        Event::listen(CollectingSeoPages::class, function (CollectingSeoPages $event): void {
            $event->add(
                'plugins.staff.public.documents.index',
                __('staff::seo.pages.index'),
                [
                    'members_count' => __('staff::seo.variables.members_count'),
                    'documents_count' => __('staff::seo.variables.documents_count'),
                ],
                defaults: ['title' => __('staff::messages.common.staff'), 'description' => '{description}'],
            );
            $event->add(
                'plugins.staff.public.documents.show',
                __('staff::seo.pages.document'),
                [
                    'document' => __('staff::seo.variables.document'),
                    'document_description' => __('staff::seo.variables.document_description'),
                ],
                defaults: ['title' => '{document}', 'description' => '{description}'],
                excerptVariables: ['document_description'],
            );
        });
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
    }

    private function registerWidgets(WidgetRegistry $widgets): void
    {
        $widgets->register(
            owner: 'staff',
            id: 'team',
            label: 'staff::widgets.team',
            render: fn (array $context, array $config) => view('staff::widgets.team', [
                'members' => app(Directory::class)
                    ->members(null, $context['user'] ?? null, $config['limit'], 1)
                    ->getCollection(),
            ]),
            fields: ['limit' => ['type' => 'integer', 'label' => 'staff::widgets.count', 'min' => 1, 'max' => 12]],
            permission: 'staff.view',
            zones: ['home'],
            defaults: ['limit' => 6],
        );
    }
}
