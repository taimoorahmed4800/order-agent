<?php

namespace App\Services;

/**
 * The agent loop:
 *   1. Send the conversation + tool list to the AI.
 *   2. If the AI asks to use a tool -> we run it, send the result back, repeat.
 *   3. When the AI answers with plain text -> that is the final answer.
 */
class OrderAgent
{
    private const MAX_STEPS = 5; // safety limit so it can never loop forever

    private const SYSTEM_PROMPT = '';

    public function __construct(private LlmClient $llm, private OrderTools $tools)
    {
    }

    /**
     * @param  array  $history  previous API messages (without the system prompt)
     * @return array{answer:string, new_messages:array, tools:array}
     */
    public function reply(array $history, string $userText): array
    {
        $userMessage = ['role' => 'user', 'content' => $userText];

        $messages    = array_merge([['role' => 'system', 'content' => self::SYSTEM_PROMPT]], $history, [$userMessage]);
        $newMessages = [$userMessage];
        $trace       = [];

        for ($step = 0; $step < self::MAX_STEPS; $step++) {
            $message = $this->llm->completion($messages, $this->tools->definitions());

            $messages[]    = $message;
            $newMessages[] = $message;

            $calls = $message['tool_calls'] ?? [];

            // No tool requested -> this is the final answer
            if (empty($calls)) {
                $answer = trim((string) ($message['content'] ?? ''));

                return [
                    'answer'       => $answer !== '' ? $answer : 'Sorry, I could not prepare an answer. Please try again.',
                    'new_messages' => $newMessages,
                    'tools'        => $trace,
                ];
            }

            // The AI asked for one or more tools: run them and send the results back
            foreach ($calls as $call) {
                
                $name = (string) ($call['function']['name'] ?? '');
                $args = json_decode($call['function']['arguments'] ?? '{}', true);
                $args = is_array($args) ? $args : [];

                $result = $this->tools->execute($name, $args);

                $trace[] = ['name' => $name, 'args' => $args, 'result' => $result];

                $toolMessage = [
                    'role'         => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'name'         => $name,
                    'content'      => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];

                $messages[]    = $toolMessage;
                $newMessages[] = $toolMessage;
            }
        }

        return [
            'answer'       => 'Sorry, I could not finish this request. Please try again with clear details.',
            'new_messages' => $newMessages,
            'tools'        => $trace,
        ];
    }
}
