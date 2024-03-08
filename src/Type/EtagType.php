<?php

namespace App\Type;

enum EtagType: string
{
    case ELEMENT = 'element';
    case PARENTS_COLLECTION = 'parents';
    case CHILDREN_COLLECTION = 'children';
    case INDEX_COLLECTION = 'index';
    case RELATED_COLLECTION = 'related';
}
