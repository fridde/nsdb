<?php

namespace App\Enums;

enum Segment: string
{
    // order is important
    case AK_2 = '2';
    case AK_5 = '5';
    case AK_9 = '9';
    case FRI = 'fri';
    case FBK = 'fbk';



    public static function getLabels(): array
    {
        return [
            self::AK_2->value => 'åk 2/3',   // the first denotes the default segment in views
            self::AK_5->value => 'åk 5',
            self::AK_9->value => 'åk 9',
            self::FRI->value => 'Fritids',
            self::FBK->value => 'FBK'
        ];
    }

    public static function getLabel(Segment $segment): string
    {
        return self::getLabels()[$segment->value];
    }

    public static function getValues(): array
    {
        return array_map(fn(Segment $s) => $s->value, self::cases());
    }

    public function getOrder(): int
    {
        return array_search($this, self::cases(), true);
    }


}