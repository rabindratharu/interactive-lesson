<?php

/**
 * PHPUnit Tests for Quiz_Endpoint Class
 *
 * @package interactive-lesson
 * @since 1.0.0
 */

namespace Interactive_Lesson\Tests;

use Interactive_Lesson\Inc\Rest_Endpoint;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Class QuizHandlerTest
 *
 * Tests the quiz-related REST endpoints.
 */
class QuizHandlerTest extends WP_UnitTestCase
{
    private $user_id;
    private $endpoint;

    /**
     * Set up before each test.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->endpoint = Rest_Endpoint::get_instance();

        // Create a test user
        $this->user_id = $this->factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($this->user_id);

        // Set up nonce for REST API
        wp_set_current_user($this->user_id);
        $this->nonce = wp_create_nonce('wp_rest');
    }

    /**
     * Tear down after each test.
     */
    public function tearDown(): void
    {
        wp_set_current_user(0);
        delete_user_meta($this->user_id, 'quiz_score_');
        delete_user_meta($this->user_id, 'quiz_answer_%');
        delete_user_meta($this->user_id, 'quiz_correct_%');
        delete_user_meta($this->user_id, 'quiz_timestamp_');
        parent::tearDown();
    }

    /**
     * Test user authentication check for unauthenticated user.
     */
    public function test_check_user_auth_unauthenticated()
    {
        wp_set_current_user(0);
        $result = $this->endpoint->check_user_auth();

        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
        $this->assertEquals(401, $result->get_error_data()['status']);
    }

    /**
     * Test user authentication check for authenticated user.
     */
    public function test_check_user_auth_authenticated()
    {
        $result = $this->endpoint->check_user_auth();
        $this->assertTrue($result);
    }

    /**
     * Test quiz submission with invalid nonce.
     */
    public function test_update_submission_invalid_nonce()
    {
        $request = new WP_REST_Request('POST', '/quiz/v1/submit');
        $request->set_header('X-WP-Nonce', 'invalid_nonce');
        $request->set_body(json_encode([
            'question' => 'What is 2+2?',
            'answer' => '4',
            'correct_answer' => '4',
        ]));

        $response = $this->endpoint->update_submission($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('invalid_nonce', $response->get_error_code());
        $this->assertEquals(403, $response->get_error_data()['status']);
    }

    /**
     * Test quiz submission with missing parameters.
     */
    public function test_update_submission_missing_params()
    {
        $request = new WP_REST_Request('POST', '/quiz/v1/submit');
        $request->set_header('X-WP-Nonce', $this->nonce);
        $request->set_body(json_encode([
            'question' => 'What is 2+2?',
            'answer' => '4',
        ]));

        $response = $this->endpoint->update_submission($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('missing_params', $response->get_error_code());
        $this->assertEquals(400, $response->get_error_data()['status']);
    }

    /**
     * Test successful quiz submission with correct answer.
     */
    public function test_update_submission_correct_answer()
    {
        $request = new WP_REST_Request('POST', '/quiz/v1/submit');
        $request->set_header('X-WP-Nonce', $this->nonce);
        $request->set_body(json_encode([
            'question' => 'What is 2+2?',
            'answer' => '4',
            'correct_answer' => '4',
        ]));

        $response = $this->endpoint->update_submission($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals('4', $data['answer']);
        $this->assertEquals('Correct!', $data['message']);
        $this->assertEquals(1, get_user_meta($this->user_id, 'quiz_score_', true));
        $this->assertEquals('1', get_user_meta($this->user_id, 'quiz_correct_' . md5('What is 2+2?'), true));
        $this->assertNotEmpty(get_user_meta($this->user_id, 'quiz_timestamp_', true));
    }

    /**
     * Test successful quiz submission with incorrect answer.
     */
    public function test_update_submission_incorrect_answer()
    {
        $request = new WP_REST_Request('POST', '/quiz/v1/submit');
        $request->set_header('X-WP-Nonce', $this->nonce);
        $request->set_body(json_encode([
            'question' => 'What is 2+2?',
            'answer' => '5',
            'correct_answer' => '4',
        ]));

        $response = $this->endpoint->update_submission($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals('5', $data['answer']);
        $this->assertEquals('Incorrect. The correct answer is 4.', $data['message']);
        $this->assertEquals(0, get_user_meta($this->user_id, 'quiz_score_', true));
        $this->assertEquals('0', get_user_meta($this->user_id, 'quiz_correct_' . md5('What is 2+2?'), true));
        $this->assertNotEmpty(get_user_meta($this->user_id, 'quiz_timestamp_', true));
    }

    /**
     * Test quiz results retrieval.
     */
    public function test_get_result()
    {
        // Simulate a submission
        $question = 'What is 2+2?';
        $question_hash = md5($question);
        update_user_meta($this->user_id, 'quiz_answer_' . $question_hash, '4');
        update_user_meta($this->user_id, 'quiz_correct_' . $question_hash, '1');
        update_user_meta($this->user_id, 'quiz_score_', 1);
        update_user_meta($this->user_id, 'quiz_timestamp_', current_time('mysql'));

        $request = new WP_REST_Request('GET', '/quiz/v1/results');
        $response = $this->endpoint->get_result($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertCount(1, $data['results']);
        $this->assertEquals($question_hash, $data['results'][0]['question_hash']);
        $this->assertEquals('4', $data['results'][0]['answer']);
        $this->assertTrue($data['results'][0]['is_correct']);
        $this->assertEquals(1, $data['total_score']);
        $this->assertNotEmpty($data['timestamp']);
    }

    /**
     * Test quiz results retrieval with no submissions.
     */
    public function test_get_result_no_submissions()
    {
        $request = new WP_REST_Request('GET', '/quiz/v1/results');
        $response = $this->endpoint->get_result($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEmpty($data['results']);
        $this->assertEquals(0, $data['total_score']);
        $this->assertEmpty($data['timestamp']);
    }
}
