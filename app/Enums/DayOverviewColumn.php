<?php

namespace App\Enums;

/**
 * The columns of the day overview table, in their default order.
 */
enum DayOverviewColumn: string
{
    case CowNumber = 'cowNumber';
    case LifeNumber = 'lifeNumber';
    case DaysInMilk = 'daysInMilk';
    case LactationNumber = 'lactationNumber';
    case MilkProduction = 'milkProduction';
    case Bcs = 'bcs';
    case FeedN1 = 'feedN1';
    case FeedN3 = 'feedN3';
    case Fpcm = 'fpcm';
    case Fat = 'fat';
    case Protein = 'protein';
    case Lactose = 'lactose';
    case Urea = 'urea';
    case CellCount = 'cellCount';
    case SampleDate = 'sampleDate';
}
