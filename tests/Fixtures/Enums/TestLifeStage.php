<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Enums;

use AndyDefer\DomainStructures\Traits\Enumable;

/**
 * Enum representing the stages of human life.
 */
enum TestLifeStage: string
{
    use Enumable;

    case UNKNOWN = 'unknown';
    case BABY = 'baby';
    case TODDLER = 'toddler';
    case CHILD = 'child';
    case PRE_TEEN = 'pre_teen';
    case TEENAGER = 'teenager';
    case YOUNG_ADULT = 'young_adult';
    case ADULT = 'adult';
    case MIDDLE_AGE = 'middle_age';
    case SENIOR = 'senior';
    case ELDERLY = 'elderly';
    case DEAD = 'dead';

    /**
     * Get the label in French.
     */
    public function getLabelFr(): string
    {
        return match ($this) {
            self::UNKNOWN => 'Inconnu',
            self::BABY => 'Bébé (0-1 an)',
            self::TODDLER => 'Petite enfance (1-3 ans)',
            self::CHILD => 'Enfant (4-11 ans)',
            self::PRE_TEEN => 'Pré-adolescent (11-13 ans)',
            self::TEENAGER => 'Adolescent (13-18 ans)',
            self::YOUNG_ADULT => 'Jeune adulte (18-25 ans)',
            self::ADULT => 'Adulte (25-40 ans)',
            self::MIDDLE_AGE => 'Âge mûr (40-60 ans)',
            self::SENIOR => 'Senior (60-75 ans)',
            self::ELDERLY => 'Grand âge (75+ ans)',
            self::DEAD => 'Décédé',
        };
    }

    /**
     * Get the label in English.
     */
    public function getLabelEn(): string
    {
        return match ($this) {
            self::UNKNOWN => 'Unknown',
            self::BABY => 'Baby (0-1 year)',
            self::TODDLER => 'Toddler (1-3 years)',
            self::CHILD => 'Child (4-11 years)',
            self::PRE_TEEN => 'Pre-teen (11-13 years)',
            self::TEENAGER => 'Teenager (13-18 years)',
            self::YOUNG_ADULT => 'Young adult (18-25 years)',
            self::ADULT => 'Adult (25-40 years)',
            self::MIDDLE_AGE => 'Middle age (40-60 years)',
            self::SENIOR => 'Senior (60-75 years)',
            self::ELDERLY => 'Elderly (75+ years)',
            self::DEAD => 'Deceased',
        };
    }

    /**
     * Get the age range as string.
     */
    public function getAgeRange(): string
    {
        return match ($this) {
            self::UNKNOWN => '?',
            self::BABY => '0-1',
            self::TODDLER => '1-3',
            self::CHILD => '4-11',
            self::PRE_TEEN => '11-13',
            self::TEENAGER => '13-18',
            self::YOUNG_ADULT => '18-25',
            self::ADULT => '25-40',
            self::MIDDLE_AGE => '40-60',
            self::SENIOR => '60-75',
            self::ELDERLY => '75+',
            self::DEAD => '∞',
        };
    }

    /**
     * Get the minimum age for this stage.
     */
    public function getMinAge(): int
    {
        return match ($this) {
            self::UNKNOWN => 0,
            self::BABY => 0,
            self::TODDLER => 1,
            self::CHILD => 4,
            self::PRE_TEEN => 11,
            self::TEENAGER => 13,
            self::YOUNG_ADULT => 18,
            self::ADULT => 25,
            self::MIDDLE_AGE => 40,
            self::SENIOR => 60,
            self::ELDERLY => 75,
            self::DEAD => 0,
        };
    }

    /**
     * Get the maximum age for this stage.
     */
    public function getMaxAge(): int
    {
        return match ($this) {
            self::UNKNOWN => 150,
            self::BABY => 1,
            self::TODDLER => 3,
            self::CHILD => 11,
            self::PRE_TEEN => 13,
            self::TEENAGER => 18,
            self::YOUNG_ADULT => 25,
            self::ADULT => 40,
            self::MIDDLE_AGE => 60,
            self::SENIOR => 75,
            self::ELDERLY => 150,
            self::DEAD => 150,
        };
    }

    /**
     * Get the emoji representing this life stage.
     */
    public function getEmoji(): string
    {
        return match ($this) {
            self::UNKNOWN => '❓',
            self::BABY => '👶',
            self::TODDLER => '🚶',
            self::CHILD => '🧒',
            self::PRE_TEEN => '🧑',
            self::TEENAGER => '🧑‍🎓',
            self::YOUNG_ADULT => '🧑‍💻',
            self::ADULT => '👨‍💼',
            self::MIDDLE_AGE => '👨‍🔧',
            self::SENIOR => '👴',
            self::ELDERLY => '👵',
            self::DEAD => '💀',
        };
    }

    /**
     * Determine life stage from age.
     */
    public static function fromAge(int $age): self
    {
        if ($age < 0) {
            return self::UNKNOWN;
        }

        return match (true) {
            $age <= 1 => self::BABY,
            $age <= 3 => self::TODDLER,
            $age <= 11 => self::CHILD,
            $age <= 13 => self::PRE_TEEN,
            $age <= 18 => self::TEENAGER,
            $age <= 25 => self::YOUNG_ADULT,
            $age <= 40 => self::ADULT,
            $age <= 60 => self::MIDDLE_AGE,
            $age <= 75 => self::SENIOR,
            default => self::ELDERLY,
        };
    }

    /**
     * Check if this stage is alive.
     */
    public function isAlive(): bool
    {
        return $this !== self::DEAD && $this !== self::UNKNOWN;
    }

    /**
     * Check if this stage is dead.
     */
    public function isDead(): bool
    {
        return $this === self::DEAD;
    }

    /**
     * Check if this stage is unknown.
     */
    public function isUnknown(): bool
    {
        return $this === self::UNKNOWN;
    }

    /**
     * Check if this stage is a child stage.
     */
    public function isChild(): bool
    {
        return in_array($this, [self::BABY, self::TODDLER, self::CHILD, self::PRE_TEEN], true);
    }

    /**
     * Check if this stage is an adult stage.
     */
    public function isAdult(): bool
    {
        return in_array($this, [self::ADULT, self::MIDDLE_AGE, self::SENIOR, self::ELDERLY], true);
    }

    /**
     * Check if this stage is a senior stage.
     */
    public function isSenior(): bool
    {
        return in_array($this, [self::SENIOR, self::ELDERLY], true);
    }

    /**
     * Check if this stage can vote (18+).
     */
    public function canVote(): bool
    {
        return $this->isAlive() && $this->getMinAge() >= 18;
    }

    /**
     * Check if this stage can drive (16+).
     */
    public function canDrive(): bool
    {
        return $this->isAlive() && $this->getMinAge() >= 16;
    }

    /**
     * Check if this stage can retire (62+).
     */
    public function canRetire(): bool
    {
        return $this->isAlive() && $this->getMinAge() >= 62;
    }

    /**
     * Get all alive stages.
     *
     * @return array<self>
     */
    public static function getAliveStages(): array
    {
        return array_filter(self::cases(), fn ($stage) => $stage->isAlive());
    }

    /**
     * Get all stages except unknown and dead.
     *
     * @return array<self>
     */
    public static function getLivingStages(): array
    {
        return self::getAliveStages();
    }
}
