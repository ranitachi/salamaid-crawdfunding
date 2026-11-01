<?php

return [
    'name' => 'Transparency',

    /*
    | Evidence files (bukti penggunaan dana). The disk must be private: files
    | are only served through the authorized admin download route.
    */
    'evidence' => [
        'disk' => env('TRANSPARENCY_EVIDENCE_DISK', 'local'),
        'directory' => 'transparency/evidence',
        'max_kilobytes' => 5120,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
        'max_files' => 10,
    ],
];
