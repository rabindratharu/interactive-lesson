<?php

/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

if (!is_array($attributes)) {
	return '';
}

$question = isset($attributes['question']) ? esc_html($attributes['question']) : '';
$options = isset($attributes['options']) ? (array) $attributes['options'] : array();
$correct_answer = isset($attributes['correctAnswer']) ? esc_html($attributes['correctAnswer']) : '';
$current_user_id = get_current_user_id();
$question_hash = md5($question);
$user_answer = $current_user_id ? get_user_meta($current_user_id, 'quiz_answer_' . $question_hash, true) : '';
$result = '';

// Check if user has submitted an answer
if ($user_answer) {
	$is_correct = $current_user_id && (get_user_meta($current_user_id, 'quiz_correct_' . $question_hash, true) === '1');
	$message = $is_correct ? 'Correct!' : 'Incorrect. The correct answer is ' . $correct_answer . '.';
	$result = '<div class="quiz-result"><p>Your answer: ' . esc_html($user_answer) . '. ' . esc_html($message) . '</p></div>';
}

ob_start();
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<div class="quiz-block">
		<h3><?php echo $question; ?></h3>
		<?php if (!$user_answer && $current_user_id) : ?>
			<form class="quiz-form" data-question="<?php echo esc_attr($question); ?>"
				data-correct-answer="<?php echo esc_attr($correct_answer); ?>">
				<?php foreach ($options as $option) : ?>
					<label>
						<input type="radio" name="quiz_answer" value="<?php echo esc_attr($option); ?>" required>
						<?php echo esc_html($option); ?>
					</label><br>
				<?php endforeach; ?>
				<button type="submit" name="submit_quiz">Submit</button>
			</form>
		<?php elseif (!$current_user_id) : ?>
			<p>Please log in to answer this quiz.</p>
		<?php endif; ?>
		<?php echo $result; ?>
	</div>
</div>
<?php
echo ob_get_clean();
