<?php

/**
 * Class for manipulation with Gmail messages
 */
namespace GmailWrapper;
use Google_Client;
use Google_Service_Gmail;

class Messages
{
    private $client;

    public function __construct($authenticate)
    {
        $this->authenticate = $authenticate;
    }

    /**
     * Get a list of all messages of the authenticated user
     * @param  string $filter Filtering query options, as used in gmail client
     * @return array          Array of all messages that match the filter
     */
    public function getMessages($filter = false)
    {
        if ($this->authenticate->getTokens()) {
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $pageToken = null;
            $messages = array();
            $opt_param = array();
            if ($filter) {
                $opt_param = array('q'=>$filter);
            }
            do {
                try {
                    if ($pageToken) {
                        $opt_param['pageToken'] = $pageToken;
                    }
                    $messagesResponse = $gmail->users_messages->listUsersMessages($this->authenticate->getUserId(), $opt_param);
                    if ($messagesResponse->getMessages()) {
                        $messages = array_merge($messages, $messagesResponse->getMessages());
                        $pageToken = $messagesResponse->getNextPageToken();
                    }
                } catch (Exception $e) {
                    print 'An error occurred: ' . $e->getMessage();
                }
                break;
            } while ($pageToken);

            return $messages;
        }
    }

    /**
     * Returns the details for a selected message
     * @param  string $message_id The id of the message
     * @param  array  $header_params The header parameters to be returned
     * @return array  Array with the message details
     */
    public function getMessageDetails($message_id, $header_params = array('From','To','Date','Subject'))
    {
        if ($this->authenticate->getTokens()) {
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $data = array();
            $opt_param = array();
            $files = array();
            try {
                $message = $gmail->users_messages->get($this->authenticate->getUserId(), $message_id, $opt_param);
                $message_details = $message['payload'];
                foreach ($message_details['headers'] as $key => $value) {
                    if (!in_array($value['name'], $header_params)) {
                        continue;
                    }
                    $data['Headers'][$value['name']] = $value['value'];
                }
                if (!is_null($message_details['body']['data'])) {
                    $data['Message'] = nl2br($this->base64UrlDecode($message_details['body']['data']));
                    return $data;
                }
                $message = '';
                foreach ($message_details['parts'] as $key => $value) {
                    if ($value['mimeType'] == 'text/plain' || $value['mimeType'] == 'text/html') {
                        $message.=nl2br($this->base64UrlDecode($value['body']['data']));
                    } else {
                        array_push($files, ['mime' => $value['mimeType'], 'data' => $value['body']['attachmentId']]);
                    }
                }
                $data['Message'] = $message;
                return $data;
            } catch (Exception $e) {
                print 'An error occurred: ' . $e->getMessage();
            }
        }
    }

    /**
     * Returns a url decoded string, used to decode the body text of the message
     * @param  String $string The string to be decoded
     * @return string         Decoded string
     */
    private function base64UrlDecode($string)
    {
        return base64_decode(str_replace(array('-', '_'), array('+', '/'), $string));
    }
}
