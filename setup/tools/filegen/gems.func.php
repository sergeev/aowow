<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


    // Create 'gems'-file for available locales
    // this script requires the following dbc-files to be parsed and available
    // ItemEnchantment, GemProperties, Spells, Icons

    /* Example
        22460: {
            name:'Prismatic Sphere',
            quality:3,
            icon:'INV_Enchant_PrismaticSphere',
            enchantment:'+3 Resist All',
            jsonequip:{"arcres":3,"avgbuyout":242980,"firres":3,"frores":3,"holres":3,"natres":3,"shares":3},
            colors:14,
            expansion:1
            gearscore:8  // as if.....
        },
    */

    function gems()
    {
        // sketchy, but should work
        // Id < 36'000 || ilevel < 70 ? BC : WOTLK
        $gems   = DB::Aowow()->Select(
           'SELECT    i.id AS itemId,
                      i.name_loc0, i.name_loc2, i.name_loc3, i.name_loc6, i.name_loc8,
                      IF (i.id < 36000 OR i.itemLevel < 70, 1 , 2) AS expansion,
                      i.quality,
                      i.iconString AS icon,
                      i.gemEnchantmentId AS enchId,
                      i.gemColorMask AS colors
            FROM      ?_items i
            WHERE     i.gemEnchantmentId <> 0
            ORDER BY  i.id DESC');
        $success = true;


        // check directory-structure
        foreach (Util::$localeStrings as $dir)
            if (!FileGen::writeDir('datasets/'.$dir))
                $success = false;

        $enchIds = [];
        foreach ($gems as $pop)
            $enchIds[] = $pop['enchId'];

        $enchMisc = [];
        $enchJSON = Util::parseItemEnchantment($enchIds, false, $enchMisc);

        foreach (FileGen::$localeIds as $lId)
        {
            set_time_limit(5);

            User::useLocale($lId);
            Lang::load(Util::$localeStrings[$lId]);

            $gemsOut = [];
            foreach ($gems as $pop)
            {
                $gemsOut[$pop['itemId']] = array(
                    'name'        => Util::localizedString($pop, 'name'),
                    'quality'     => $pop['quality'],
                    'icon'        => strToLower($pop['icon']),
                    'enchantment' => Util::localizedString(@$enchMisc[$pop['enchId']]['text'] ?: [], 'text'),
                    'jsonequip'   => @$enchJSON[$pop['enchId']] ?: [],
                    'colors'      => $pop['colors'],
                    'expansion'   => $pop['expansion']
                );
            }

            $toFile = "var g_gems = ".Util::toJSON($gemsOut).";";
            $file   = 'datasets/'.User::$localeString.'/gems';

            if (!FileGen::writeFile($file, $toFile))
                $success = false;
        }

        return $success;
    }

?>
