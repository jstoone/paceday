<?php

declare(strict_types=1);

use App\Support\Verbs\VerbsTypeMapper;

describe('VerbsTypeMapper', function () {
    beforeEach(function () {
        $this->mapper = new VerbsTypeMapper;
    });

    describe('fqcnToAlias', function () {
        it('converts event FQCN to alias', function () {
            $alias = $this->mapper->fqcnToAlias('App\\Domain\\Tracking\\Events\\QuestionAsked', isState: false);

            expect($alias)->toBe('tracking.question-asked');
        });

        it('converts state FQCN to alias and strips State suffix', function () {
            $alias = $this->mapper->fqcnToAlias('App\\Domain\\Tracking\\States\\QuestionState', isState: true);

            expect($alias)->toBe('tracking.question');
        });

        it('handles multi-word event names', function () {
            $alias = $this->mapper->fqcnToAlias('App\\Domain\\Tracking\\Events\\RoundStartDateAdjusted', isState: false);

            expect($alias)->toBe('tracking.round-start-date-adjusted');
        });

        it('handles multi-word state names', function () {
            $alias = $this->mapper->fqcnToAlias('App\\Domain\\Tracking\\States\\ActiveRoundState', isState: true);

            expect($alias)->toBe('tracking.active-round');
        });

        it('throws for class outside Domain namespace', function () {
            $this->mapper->fqcnToAlias('App\\Models\\User', isState: false);
        })->throws(InvalidArgumentException::class);

        it('throws for class without Events/States namespace', function () {
            $this->mapper->fqcnToAlias('App\\Domain\\Tracking\\Services\\SomeService', isState: false);
        })->throws(InvalidArgumentException::class);
    });

    describe('aliasToFqcn', function () {
        it('converts alias to event FQCN', function () {
            $fqcn = $this->mapper->aliasToFqcn('tracking.question-asked', isState: false);

            expect($fqcn)->toBe('App\\Domain\\Tracking\\Events\\QuestionAsked');
        });

        it('converts alias to state FQCN with State suffix', function () {
            $fqcn = $this->mapper->aliasToFqcn('tracking.question', isState: true);

            expect($fqcn)->toBe('App\\Domain\\Tracking\\States\\QuestionState');
        });

        it('reverses multi-word event names', function () {
            $fqcn = $this->mapper->aliasToFqcn('tracking.round-start-date-adjusted', isState: false);

            expect($fqcn)->toBe('App\\Domain\\Tracking\\Events\\RoundStartDateAdjusted');
        });

        it('reverses multi-word state names', function () {
            $fqcn = $this->mapper->aliasToFqcn('tracking.active-round', isState: true);

            expect($fqcn)->toBe('App\\Domain\\Tracking\\States\\ActiveRoundState');
        });

        it('throws for invalid alias format without dot', function () {
            $this->mapper->aliasToFqcn('invalid', isState: false);
        })->throws(InvalidArgumentException::class);
    });

    describe('roundtrip conversions', function () {
        it('event FQCN roundtrips correctly', function () {
            $original = 'App\\Domain\\Tracking\\Events\\RoundStartDateAdjusted';

            $alias = $this->mapper->fqcnToAlias($original, isState: false);
            $restored = $this->mapper->aliasToFqcn($alias, isState: false);

            expect($restored)->toBe($original);
        });

        it('state FQCN roundtrips correctly', function () {
            $original = 'App\\Domain\\Tracking\\States\\RoundState';

            $alias = $this->mapper->fqcnToAlias($original, isState: true);
            $restored = $this->mapper->aliasToFqcn($alias, isState: true);

            expect($restored)->toBe($original);
        });

        it('event alias roundtrips correctly', function () {
            $original = 'tracking.round-ended';

            $fqcn = $this->mapper->aliasToFqcn($original, isState: false);
            $restored = $this->mapper->fqcnToAlias($fqcn, isState: false);

            expect($restored)->toBe($original);
        });

        it('state alias roundtrips correctly', function () {
            $original = 'tracking.round';

            $fqcn = $this->mapper->aliasToFqcn($original, isState: true);
            $restored = $this->mapper->fqcnToAlias($fqcn, isState: true);

            expect($restored)->toBe($original);
        });
    });

    describe('isAlias', function () {
        it('returns true for valid alias format', function () {
            expect($this->mapper->isAlias('tracking.question-asked'))->toBeTrue();
            expect($this->mapper->isAlias('tracking.round'))->toBeTrue();
        });

        it('returns false for FQCN', function () {
            expect($this->mapper->isAlias('App\\Domain\\Tracking\\Events\\QuestionAsked'))->toBeFalse();
        });

        it('returns false for string without dot', function () {
            expect($this->mapper->isAlias('QuestionAsked'))->toBeFalse();
        });
    });

    describe('isFqcn', function () {
        it('returns true for FQCN', function () {
            expect($this->mapper->isFqcn('App\\Domain\\Tracking\\Events\\QuestionAsked'))->toBeTrue();
        });

        it('returns false for alias', function () {
            expect($this->mapper->isFqcn('tracking.question-asked'))->toBeFalse();
        });
    });
});
