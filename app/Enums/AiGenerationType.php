<?php

namespace App\Enums;

enum AiGenerationType: string
{
    case FacadeRender = 'facade_render';
    case Floorplan = 'floorplan';
    case Other = 'other';
}
