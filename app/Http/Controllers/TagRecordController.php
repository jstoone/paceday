<?php

namespace App\Http\Controllers;

use App\Domain\Tracking\Actions\EndRound;
use App\Domain\Tracking\Actions\LogUsage;
use App\Domain\Tracking\Actions\StartRound;
use App\Domain\Tracking\QuestionType;
use App\Domain\Tracking\States\QuestionState;
use App\Models\Tag;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagRecordController extends Controller
{
    public function __invoke(Request $request, string $code): JsonResponse
    {
        $tag = Tag::where('code', $code)->first();

        if (! $tag || ! $tag->question_id) {
            return response()->json([
                'error' => 'Tag not found or not linked to a question.',
            ], 404);
        }

        $question = $tag->question;
        $questionState = QuestionState::load($question->id);

        $note = $request->input('note');

        if ($questionState->question_type === QuestionType::Frequency) {
            app(LogUsage::class)->execute(
                question_id: $question->id,
                note: $note,
            );

            return response()->json([
                'status' => 'recorded',
                'action' => 'usage_logged',
                'question' => $question->label,
            ]);
        }

        if ($questionState->active_round_id !== null) {
            app(EndRound::class)->execute(
                round_id: $questionState->active_round_id,
                occurred_at: CarbonImmutable::now(),
                note: $note,
            );

            return response()->json([
                'status' => 'recorded',
                'action' => 'round_ended',
                'question' => $question->label,
            ]);
        }

        app(StartRound::class)->execute(
            question_id: $question->id,
            note: $note,
        );

        return response()->json([
            'status' => 'recorded',
            'action' => 'round_started',
            'question' => $question->label,
        ]);
    }
}
