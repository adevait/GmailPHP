<?php

/**
 * Class for manipulation with Gmail messages
 */
namespace GmailWrapper;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Google_Service_Gmail_MessagePart;
use Google_Service_Gmail_MessagePartHeader;
use Google_Service_Gmail_MessagePartBody;
use PHPMailer;

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
    public function getMessageDetails($message_id, $header_params = array('From', 'To', 'Date', 'Subject'))
    {
        if ($this->authenticate->getTokens()) {
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $data = array();
            $opt_param = array();
            $files = array();
            try {
                $message = $gmail->users_messages->get($this->authenticate->getUserId(), $message_id, $opt_param);
                echo '<pre>';
                var_dump($message);
                exit;
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
     * Prepares the email and sends it using Google_Service_Gmail_Message
     * @param  string $to      Email address to which the message should be sent
     * @param  string $subject The subject of the email
     * @param  string $body    The body of the email
     * @return Google_Service_Gmail_Message  The sent message
     */
    public function send($to, $subject, $body)
    {
        if ($this->authenticate->getTokens()) {
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $message = new Google_Service_Gmail_Message();
            $mail = new PHPMailer();
            $user = $this->authenticate->getUserDetails();
            $mail->From = $user['email'];
            $mail->FromName = $user['email'];
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->preSend();
            $mime = $mail->getSentMIMEMessage();
            $raw = $this->Base64UrlEncode($mime);
            $message->setRaw($raw);
            return $gmail->users_messages->send($this->authenticate->getUserId(), $message);
        }
    }

    /**
     * Returns a base64 decoded web safe string
     * @param  String $string The string to be decoded
     * @return string Decoded string
     */
    private function base64UrlDecode($string)
    {
        return base64_decode(str_replace(array('-', '_'), array('+', '/'), $string));
    }

    /**
     * Returns a web safe base64 encoded string, used for encoding
     * @param String $string The string to be encoded
     * @return String Encoded string
     */
    private function Base64UrlEncode($string)
    {
        return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
    }
}
