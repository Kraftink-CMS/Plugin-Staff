<?php

namespace KraftInk\Plugins\Staff\Services\Imports;

use App\Kraft\Migration\MappingRegistry;
use KraftInk\Plugins\Staff\Services\Imports\KraftInk\Mappings as KraftInkMappings;
use KraftInk\Plugins\Staff\Services\Imports\MineWeb\Mappings;

final class ImportMappings
{
    public function register(MappingRegistry $registry): void
    {
        new Mappings()->register($registry);
        new KraftInkMappings()->register($registry);
    }
}
