<?php

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'Admin';
    case AcademyManager = 'Academy Manager';
    case Instructor = 'Instructor';
    case Student = 'Student';
}
