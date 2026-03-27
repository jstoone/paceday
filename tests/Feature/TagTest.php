<?php

use App\Domain\Tracking\Actions\CreateTag;
use App\Domain\Tracking\Actions\UnlinkTag;
use App\Domain\Tracking\Data\DurationQuestion;
use App\Domain\Tracking\Events\QuestionAsked;
use App\Domain\Tracking\Events\RoundStarted;
use App\Domain\Tracking\Events\TagCreated;
use App\Domain\Tracking\Events\TagLinked;
use App\Domain\Tracking\Events\TagUnlinked;
use App\Domain\Tracking\States\TagState;
use App\Models\Tag;
use App\Models\User;
use Thunk\Verbs\Facades\Verbs;

describe('TagCreated event', function () {
    it('creates a tag state with a code and user', function () {
        $user = User::factory()->create();

        $event = verb(new TagCreated(
            code: 'ab12',
            user_id: $user->id,
        ));
        Verbs::commit();

        $state = TagState::load($event->tag_id);

        expect($state->code)->toBe('ab12')
            ->and($state->user_id)->toBe($user->id)
            ->and($state->question_id)->toBeNull();
    });

    it('projects a tag to the database', function () {
        $user = User::factory()->create();

        $event = verb(new TagCreated(
            code: 'cd34',
            user_id: $user->id,
        ));
        Verbs::commit();

        $tag = Tag::find($event->tag_id);

        expect($tag)->not->toBeNull()
            ->and($tag->code)->toBe('cd34')
            ->and($tag->user_id)->toBe($user->id)
            ->and($tag->question_id)->toBeNull();
    });
});

describe('TagLinked event', function () {
    it('links a tag to a question', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How long does 40 capsules of coffee last?',
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        ));

        $tagEvent = verb(new TagCreated(
            code: 'ef56',
            user_id: $user->id,
        ));
        Verbs::commit();

        verb(new TagLinked(
            tag_id: $tagEvent->tag_id,
            question_id: $questionEvent->question_id,
        ));
        Verbs::commit();

        $state = TagState::load($tagEvent->tag_id);
        $tag = Tag::find($tagEvent->tag_id);

        expect($state->question_id)->toBe($questionEvent->question_id)
            ->and($tag->question_id)->toBe($questionEvent->question_id);
    });
});

describe('TagUnlinked event', function () {
    it('removes the link between a tag and a question', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How long does 40 capsules of coffee last?',
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        ));

        $tagEvent = verb(new TagCreated(
            code: 'gh78',
            user_id: $user->id,
        ));

        verb(new TagLinked(
            tag_id: $tagEvent->tag_id,
            question_id: $questionEvent->question_id,
        ));
        Verbs::commit();

        verb(new TagUnlinked(
            tag_id: $tagEvent->tag_id,
        ));
        Verbs::commit();

        $state = TagState::load($tagEvent->tag_id);
        $tag = Tag::find($tagEvent->tag_id);

        expect($state->question_id)->toBeNull()
            ->and($tag->question_id)->toBeNull();
    });

    it('prevents unlinking a tag that is not linked', function () {
        $user = User::factory()->create();

        $tagEvent = verb(new TagCreated(
            code: 'ij90',
            user_id: $user->id,
        ));
        Verbs::commit();

        verb(new TagUnlinked(
            tag_id: $tagEvent->tag_id,
        ));
    })->throws(Exception::class, 'Tag is not linked to a question.');
});

describe('CreateTag action', function () {
    it('creates a tag linked to a question', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How long does 40 capsules of coffee last?',
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        ));
        Verbs::commit();

        $tag = app(CreateTag::class)->execute(
            user_id: $user->id,
            question_id: $questionEvent->question_id,
        );

        expect($tag)->toBeInstanceOf(Tag::class)
            ->and($tag->code)->toHaveLength(4)
            ->and($tag->question_id)->toBe($questionEvent->question_id)
            ->and($tag->user_id)->toBe($user->id);
    });

    it('generates a unique 4-character code', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How long does 40 capsules of coffee last?',
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        ));
        Verbs::commit();

        $tag1 = app(CreateTag::class)->execute(
            user_id: $user->id,
            question_id: $questionEvent->question_id,
        );

        $tag2 = app(CreateTag::class)->execute(
            user_id: $user->id,
            question_id: $questionEvent->question_id,
        );

        expect($tag1->code)->not->toBe($tag2->code);
    });
});

describe('UnlinkTag action', function () {
    it('unlinks a tag from its question', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How long does 40 capsules of coffee last?',
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        ));
        Verbs::commit();

        $tag = app(CreateTag::class)->execute(
            user_id: $user->id,
            question_id: $questionEvent->question_id,
        );

        app(UnlinkTag::class)->execute(tag_id: $tag->id);

        $tag->refresh();

        expect($tag->question_id)->toBeNull();
    });
});

describe('Tag recording surface', function () {
    it('shows the tag confirmation page for a linked tag', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How long does 40 capsules of coffee last?',
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        ));
        Verbs::commit();

        $tag = app(CreateTag::class)->execute(
            user_id: $user->id,
            question_id: $questionEvent->question_id,
        );

        $response = $this->get(route('tags.show', $tag->code));

        $response->assertOk()
            ->assertSee('How long does 40 capsules of coffee last?');
    });

    it('shows an error for an invalid tag code', function () {
        $response = $this->get(route('tags.show', 'zzzz'));

        $response->assertOk()
            ->assertSee('Tag not found');
    });

    it('ends an active round via POST', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How long does 40 capsules of coffee last?',
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        ));

        verb(new RoundStarted(
            question_id: $questionEvent->question_id,
        ));
        Verbs::commit();

        $tag = app(CreateTag::class)->execute(
            user_id: $user->id,
            question_id: $questionEvent->question_id,
        );

        $response = $this->postJson(route('tags.record', $tag->code));

        $response->assertOk()
            ->assertJson([
                'status' => 'recorded',
                'action' => 'round_ended',
            ]);
    });

    it('starts a new round via POST when no active round', function () {
        $user = User::factory()->create();

        $questionEvent = verb(new QuestionAsked(
            user_id: $user->id,
            label: 'How long does 40 capsules of coffee last?',
            question: new DurationQuestion(thing: 'coffee', unit: 'capsules', amount: 40),
        ));
        Verbs::commit();

        $tag = app(CreateTag::class)->execute(
            user_id: $user->id,
            question_id: $questionEvent->question_id,
        );

        $response = $this->postJson(route('tags.record', $tag->code));

        $response->assertOk()
            ->assertJson([
                'status' => 'recorded',
                'action' => 'round_started',
            ]);
    });

    it('returns 404 for an unlinked tag via POST', function () {
        $user = User::factory()->create();

        $tagEvent = verb(new TagCreated(
            code: 'xx99',
            user_id: $user->id,
        ));
        Verbs::commit();

        $response = $this->postJson(route('tags.record', 'xx99'));

        $response->assertNotFound();
    });

    it('returns 404 for a nonexistent tag via POST', function () {
        $response = $this->postJson(route('tags.record', 'nope'));

        $response->assertNotFound();
    });
});
