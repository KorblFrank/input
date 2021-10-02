<?php

namespace Tests\Feature\Forms;

use Tests\TestCase;
use App\Models\FormBlock;
use App\Models\FormSession;
use App\Models\FormBlockInteraction;
use App\Models\FormSessionResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResultsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_get_summarized_results_for_a_form()
    {
        $block = FormBlock::factory()->create();

        $interactionA = FormBlockInteraction::factory()->create([
            'type' => FormBlockInteraction::TYPE_CLICK,
            'label' => 'Yes',
            'form_block_id' => $block->id,
        ]);
        $interactionB = FormBlockInteraction::factory()->create([
            'type' => FormBlockInteraction::TYPE_CLICK,
            'label' => 'Yes',
            'form_block_id' => $block->id,
        ]);

        for ($i = 0; $i < 2; $i++) {
            $session = FormSession::factory()->create([
                'form_id' => $block->form->id,
            ]);

            FormSessionResponse::factory()->create([
                'form_block_id'  => $block->id,
                'form_block_interaction_id' => $interactionA->id, // This uses Interaction A
                'form_session_id' => $session->id,
            ]);
        }

        for ($i = 0; $i < 1; $i++) {
            $session = FormSession::factory()->create([
                'form_id' => $block->form->id,
            ]);

            FormSessionResponse::factory()->create([
                'form_block_id'  => $block->id,
                'form_block_interaction_id' => $interactionB->id, // This uses Interaction B
                'form_session_id' => $session->id,
            ]);
        }

        $response = $this->actingAs($block->form->user)
            ->json('GET', route('api.forms.results.show', $block->form->uuid));

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('blocks')[0]['interactions'][0]['responses_count']);
        $this->assertEquals(1, $response->json('blocks')[0]['interactions'][1]['responses_count']);
    }

    /** @test */
    public function can_get_single_responses_for_an_input_interaction()
    {
        $interaction = FormBlockInteraction::factory()->create();

        FormSessionResponse::factory()->count(10)->create([
            'form_block_interaction_id' => $interaction->id,
        ]);

        $response = $this->actingAs($interaction->block->form->user)
            ->json('GET', route('api.interactions.results.show', $interaction->id));

        $this->assertCount(10, $response->json('responses'));
    }
}