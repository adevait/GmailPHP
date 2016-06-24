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
use Google_Service_Gmail_Label;
use Google_Service_Gmail_Draft;
use Google_Service_Gmail_ModifyMessageRequest;
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
     * @param  string $PageToken The page identifier - messages are loaded page by page to avoid overload
     * @return array       Status and data/error message depending on the success of the operation
     */
    public function getMessages($optParams = array(), $pageToken = false)
    {
        try {
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $messages = [];
            if ($pageToken) {
                $optParams['pageToken'] = $pageToken;
            }
            $messagesResponse = $gmail->users_messages->listUsersMessages($this->authenticate->getUserId(), $optParams);
            if ($messagesResponse->getMessages()) {
                $messages = array_merge($messages, $messagesResponse->getMessages());
                $nextPageToken = $messagesResponse->getNextPageToken();
                return ['status' => true, 'data' => $messages, 'nextToken' => $nextPageToken];
            }
        } catch (\Google_Service_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        } catch(\Google_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Returns the details for a selected message
     * @param  string $messageId The id of the message
     * @return array       Status and data/error message depending on the success of the operation
     */
    public function getMessageDetails($messageId)
    {
        try {
            $optParam = [];
            $data = [];
            $headers = [];
            $body = ['text/plain' => [], 'text/html' => []];
            $files = [];
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $message = $gmail->users_messages->get($this->authenticate->getUserId(), $messageId, $optParam);
            $messageDetails = $message->getPayload();
            foreach ($messageDetails['headers'] as $item) {
                $headers[$item->name] = $item->value;
            }
            $data['headers'] = $headers;
            if (!is_null($messageDetails['body']['data'])) {
                array_push($body['text/plain'], nl2br($this->base64UrlDecode($messageDetails['body']['data'])));
            }
            foreach ($messageDetails['parts'] as $key => $value) {
                if (isset($value['body']['data'])) {
                    array_push($body[$value['mimeType']], nl2br($this->base64UrlDecode($value['body']['data'])));
                } else {
                    array_push($files, $value['partId']);
                }
            }
            $data['body'] = $body;
            $data['threadId'] = $message->getThreadId();
            $data['labelIds'] = $message->getLabelIds();
            $data['snippet'] = $message->getSnippet();
            $data['files'] = $files;
            return ['status' => true, 'data' => $data];
        } catch (\Google_Service_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        } catch(\Google_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Returns the detailed attachment data
     * @param  string $messageId The id of the message
     * @param  int $partId    The id of the part of the given message, that references the selected attachment
     * @return array       Status and data/error message depending on the success of the operation
     */
    public function getAttachment($messageId, $partId)
    {
        try {
            $files = [];
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $attachmentDetails = $this->getAttachmentDetailsFromMessage($messageId, $partId);
            if (!$attachmentDetails['status']) {
                return $attachmentDetails;
            }
            $attachment = $gmail->users_messages_attachments->get($this->authenticate->getUserId(), $messageId, $attachmentDetails['attachmentId']);
            $attachmentDetails['data'] = $this->base64UrlDecode($attachment->data);
            return ['status' => true, 'data' => $attachmentDetails];
        } catch (\Google_Service_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        } catch(\Google_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Returns the attachments details from the message data, so a downloadable file can be created
     * @param  string $messageId The id of the message
     * @param  int $partId    The id of the part of the given message, that references the selected attachment
     * @return array       Status and data/error message depending on the success of the operation
     */
    private function getAttachmentDetailsFromMessage($messageId, $partId)
    {
        try {
            $attachmentHeaders = [];
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $message = $gmail->users_messages->get($this->authenticate->getUserId(), $messageId);
            $messageDetails = $message->getPayload();
            foreach ($messageDetails['parts'][$partId]['headers'] as $item) {
                $attachmentHeaders[$item->name] = $item->value;
            }
            return ['status' => true, 'mimeType' => $messageDetails['parts'][$partId]['mimeType'], 'filename' => $messageDetails['parts'][$partId]['filename'] ,'headers' => $attachmentHeaders, 'attachmentId' => $messageDetails['parts'][$partId]['body']['attachmentId']];
        } catch (\Google_Service_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        } catch(\Google_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Prepares the email and sends it using Google_Service_Gmail_Message
     * @param  string $to      Email address to which the message should be sent
     * @param  string $subject The subject of the email
     * @param  string $body    The body of the email
     * @param  array $attachment An array with name and tmp_name (in the exact order) parameters for every attachment that should be uploaded
     * @param  string $threadId The id of the thread if the message is a reply to a recieved message, false otherwise 
     * @return array       Status and data/error message depending on the success of the operation
     */
    public function send($to, $subject, $body, $attachment = array(), $threadId = false)
    {
        try {
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $message = new Google_Service_Gmail_Message();
            $userId = $this->authenticate->getUserId();
            $this->createMessage($gmail, $message, $userId, $to, $subject, $body, $attachment, $threadId);
            $response = $gmail->users_messages->send($userId, $message);
            return ['status' => true, 'data' => $response];
        } catch (\Google_Service_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        } catch(\Google_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Creates a draft message
     * @param  string  $to         Email address of the recepient
     * @param  string  $subject    Subject of the message
     * @param  string  $body       The message body
     * @param  array   $attachment An array with name and tmp_name (in the exact order) parameters for every attachment that should be uploaded
     * @param  string $threadId    The id of the thread if the message is a reply to a recieved message, false otherwise 
     * @return array       Status and data/error message depending on the success of the operation
     */
    public function createDraft($to, $subject, $body, $attachment = array(), $threadId = false)
    {
        try {
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $message = new Google_Service_Gmail_Message();
            $userId = $this->authenticate->getUserId();
            $this->createMessage($gmail, $message, $userId, $to, $subject, $body, $attachment, $threadId);
            $draft = new Google_Service_Gmail_Draft();
            $draft->setMessage($message);
            $response = $gmail->users_drafts->create($userId, $draft);
            return ['status' => true, 'data' => $response];
        } catch (\Google_Service_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        } catch(\Google_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }


    /**
     * Helper function used for creating a message structure to use when sending a message or creating a draft
     * @param  Google_Service_Gmail  &$gmail     Instance of the gmail service
     * @param  Google_Service_Gmail_Message  &$message   Instance of the gmail message
     * @param  string  $userId     Id of the user
     * @param  string  $to         Email address of the recepient
     * @param  string  $subject    Subject of the message
     * @param  string  $body       The message body
     * @param  array   $attachment An array with name and tmp_name (in the exact order) parameters for every attachment that should be uploaded
     * @param  string $threadId    The id of the thread if the message is a reply to a recieved message, false otherwise 
     */
    private function createMessage(Google_Service_Gmail &$gmail, Google_Service_Gmail_Message &$message, $userId, $to, $subject, $body, $attachment = array(), $threadId = false)
    {
        $optParam = array();
        $referenceId = '';
        if ($threadId) {
            $thread = $gmail->users_threads->get($userId, $threadId);
            // If the message should be added in the same thread, override the sent subject and use the one from the thread
            if ($thread) {
                $optParam['threadId'] = $threadId;
                $threadMessages = $thread->getMessages($optParam);
                if ($threadMessages) {
                    $messageId = $threadMessages[0]->getId();
                    $messageDetails = $this->getMessageDetails($messageId);
                    $subject = $messageDetails['headers']['Subject'];
                    $referenceId = $messageDetails['headers']['Message-Id'];
                }
            }
        }
        $mail = new PHPMailer();
        $user = $this->authenticate->getUserDetails();
        $mail->CharSet = 'UTF-8';
        $mail->From = $user['email'];
        $mail->FromName = $user['email'];
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        // set this dinamically
        $mail->IsHTML(true);
        if (!empty($attachment)) {
            foreach ($attachment as $key => $value) {
                $attachmentParams = array_combine(['name', 'tmpName'], $value);
                $mail->addAttachment($attachmentParams['tmpName'], $attachmentParams['name']);
            }
        }
        $mail->preSend();
        $mime = $mail->getSentMIMEMessage();
        $raw = $this->Base64UrlEncode($mime);
        $message->setRaw($raw);
        if ($threadId) {
            $message->setThreadId($threadId);
        }
    }

    /**
     * Deletes a message 
     * @param  string $messageId The id of the message that needs to be deleted
     * @return array       Status and data/error message depending on the success of the operation
     */
    public function trash($messageId)
    {
        try {
            $optParam = array();
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            return ['status' => true, 'data' => $gmail->users_messages->trash($this->authenticate->getUserId(), $messageId)];
        } catch (\Google_Service_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        } catch(\Google_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Undos a delete operation
     * @param  string $messageId The id of the deleted message that needs to be retrieved
     * @return array       Status and data/error message depending on the success of the operation
     */
    public function untrash($messageId)
    {
        try {
            $optParam = array();
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            return ['status' => true, 'data' => $gmail->users_messages->untrash($this->authenticate->getUserId(), $messageId)];
        } catch (\Google_Service_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        } catch(\Google_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Returns a list of all labels
     * @return array       Status and data/error message depending on the success of the operation
     */
    public function getLabels()
    {
        try {
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $labelsResponse = $gmail->users_labels->listUsersLabels($this->authenticate->getUserId());
            $labels = $labelsResponse->getLabels();
            return ['status' => true, 'data' => $labels];
        } catch (\Google_Service_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        } catch(\Google_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Returns the details for a given label
     * @param  string $labelId Id of the selected label
     * @return array          Status and data/message depending on the success of the operation
     */
    public function getLabelDetails($labelId)
    {
        try {
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $label = $gmail->users_labels->get($this->authenticate->getUserId(), $labelId);
            return ['status' => true, 'data' => $label];
        } catch (\Google_Service_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        } catch(\Google_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Creates a new label
     * @param  string $name Name of the label
     * @return array       Status and data/error message depending on the success of the operation
     */
    public function createLabel($name)
    {
        try {
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $label = new Google_Service_Gmail_Label();
            $label->setName($name);
            $response = $gmail->users_labels->create($this->authenticate->getUserId(), $label);
            return ['status' => true, 'data' => $label];
        } catch (\Google_Service_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        } catch(\Google_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Adds/Removes label from the given message
     * @param string $messageId The id of the message
     * @param array  $addIds    Ids of the labels to be added
     * @param array  $removeIds Ids of the labels to be removed
     * @return array       Status and data/error message depending on the success of the operation
     */
    public function addRemoveLabels($messageId, $addIds = array(), $removeIds = array())
    {
        try {
            $gmail = new Google_Service_Gmail($this->authenticate->getClient());
            $modifyMessageRequest = new Google_Service_Gmail_ModifyMessageRequest();
            if (!empty($addIds)) {
                $modifyMessageRequest->setAddLabelIds($addIds);
            }
            if (!empty($removeIds)) {
                $modifyMessageRequest->setRemoveLabelIds($removeIds);
            }
            $response = $gmail->users_messages->modify($this->authenticate->getUserId(), $messageId, $modifyMessageRequest);
            return ['status' => true, 'data' => $response];
        } catch (\Google_Service_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        } catch(\Google_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
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
