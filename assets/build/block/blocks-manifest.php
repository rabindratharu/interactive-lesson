<?php
// This file is generated. Do not modify it manually.
return array(
	'quiz-block' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'interactive-lesson/quiz-block',
		'version' => '1.0.0',
		'title' => 'Quiz Block',
		'category' => 'interactive-lesson',
		'icon' => 'welcome-learn-more',
		'description' => 'Quiz block for interactive lessons.',
		'example' => array(
			
		),
		'attributes' => array(
			'question' => array(
				'type' => 'string',
				'default' => 'What is the capital of France?'
			),
			'options' => array(
				'type' => 'array',
				'default' => array(
					'Paris',
					'London',
					'Berlin',
					'Madrid'
				)
			),
			'correctAnswer' => array(
				'type' => 'string',
				'default' => 'Paris'
			)
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'quiz-block',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScript' => 'file:./view.js'
	)
);
