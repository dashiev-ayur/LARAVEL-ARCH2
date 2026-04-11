<?php

namespace App\Enums;

enum OrgStatus: string
{
    case New = 'new';
    case Enabled = 'enabled';
    case Deleted = 'deleted';
}
