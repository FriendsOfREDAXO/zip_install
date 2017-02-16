<?php
    $tmpPath = rex_path::addon('zip_install','tmp');
    if(!rex_dir::isWritable($tmpPath)) {
        if(is_dir($tmpPath)) {
            @chmod($tmpPath, 0755);
        } else {
            @mkdir($tmpPath, 0755);
        }
    }
