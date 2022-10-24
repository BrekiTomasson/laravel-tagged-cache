<?php

declare(strict_types=1);

namespace BrekiTomasson\LaravelTaggedCache;

use RuntimeException;

/**
 * @method static int ONE_MINUTE()
 * @method static int TWO_MINUTES()
 * @method static int THREE_MINUTES()
 * @method static int FIVE_MINUTES()
 * @method static int TEN_MINUTES()
 * @method static int FIFTEEN_MINUTES()
 * @method static int TWENTY_MINUTES()
 * @method static int THIRTY_MINUTES()
 * @method static int FORTYFIVE_MINUTES()
 * @method static int ONE_HOUR()
 * @method static int TWO_HOURS()
 * @method static int THREE_HOURS()
 * @method static int FOUR_HOURS()
 * @method static int FIVE_HOURS()
 * @method static int SIX_HOURS()
 * @method static int TWELVE_HOURS()
 * @method static int ONE_DAY()
 * @method static int TWO_DAYS()
 * @method static int THREE_DAYS()
 * @method static int FOUR_DAYS()
 * @method static int FIVE_DAYS()
 * @method static int SIX_DAYS()
 * @method static int ONE_WEEK()
 * @method static int TWO_WEEKS()
 * @method static int THREE_WEEKS()
 * @method static int FOUR_WEEKS()
 */
enum TimeSpan: int
{
    case FIFTEEN_MINUTES = 900;

    case FIVE_DAYS = 432_000;

    case FIVE_HOURS = 18_000;

    case FIVE_MINUTES = 300;

    case FORTYFIVE_MINUTES = 2_700;

    case FOUR_DAYS = 345_600;

    case FOUR_HOURS = 14_400;

    case FOUR_WEEKS = 2_419_200;

    case ONE_DAY = 86_400;

    case ONE_HOUR = 3_600;

    case ONE_MINUTE = 60;

    case ONE_WEEK = 604_800;

    case SIX_DAYS = 518_400;

    case SIX_HOURS = 21_600;

    case TEN_MINUTES = 600;

    case THIRTY_MINUTES = 1_800;

    case THREE_DAYS = 259_200;

    case THREE_HOURS = 10_800;

    case THREE_MINUTES = 180;

    case THREE_WEEKS = 1_814_400;

    case TWELVE_HOURS = 43_200;

    case TWENTY_MINUTES = 1_200;

    case TWO_DAYS = 172_800;

    case TWO_HOURS = 7_200;

    case TWO_MINUTES = 120;

    case TWO_WEEKS = 1_209_600;

    /** @throws RuntimeException */
    public static function __callStatic(string $name, mixed $arguments): int
    {
        $cases = self::cases();

        foreach ($cases as $case) {
            if ($case->name === $name) {
                return $case->value;
            }
        }

        throw new RuntimeException("Undefined TimeSpan: ${name}");
    }

    public function __invoke(): int
    {
        return $this->value;
    }

    public static function days(int $days): int
    {
        return $days * self::ONE_DAY->value;
    }

    public static function hours(int $hours): int
    {
        return $hours * self::ONE_HOUR->value;
    }

    public static function minutes(int $minutes): int
    {
        return $minutes * self::ONE_MINUTE->value;
    }

    public static function weeks(int $weeks): int
    {
        return $weeks * self::ONE_WEEK->value;
    }
}
