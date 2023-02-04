<?php

function openai_api_query($api_key, $post_content, $categories_list)
{
    $prompt = "What is the most representative category between " . implode(" and ", $categories_list) . " on the following text:" . $post_content . ". Only give me the category. Say nothing else.";
    $tokens = explode(" ", $prompt);
    $token_count = count($tokens);

    if ($token_count > 2048) {
        $prompt = "";
        $counter = 0;
        foreach ($tokens as $token) {
            if ($counter == 2048) {
                break;
            }
            $prompt .= $token . " ";
            $counter++;
        }
        $prompt = rtrim($prompt);
    }

    $request_body = array(
        'model' => "text-davinci-003",
        'prompt' => $prompt,
        'max_tokens' => 2048,
        'temperature' => 1
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/completions');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    curl_close($ch);

    $response_data = json_decode($response, true);

    return $response_data['choices'][0]['text'];
}
