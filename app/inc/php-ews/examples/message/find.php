<?php
require_once '../../vendor/autoload.php';

use \jamesiarmes\PhpEws\Client;
use \jamesiarmes\PhpEws\Request\FindItemType;
use \jamesiarmes\PhpEws\Request\GetAttachmentType;
use \jamesiarmes\PhpEws\Request\GetItemType;

use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfRequestAttachmentIdsType;

use \jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use \jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use \jamesiarmes\PhpEws\Enumeration\ResponseClassType;
use \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use \jamesiarmes\PhpEws\Enumeration\FolderQueryTraversalType;

use \jamesiarmes\PhpEws\Type\AndType;
use \jamesiarmes\PhpEws\Type\ConstantValueType;
use \jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use \jamesiarmes\PhpEws\Type\FieldURIOrConstantType;
use \jamesiarmes\PhpEws\Type\IsGreaterThanOrEqualToType;
use \jamesiarmes\PhpEws\Type\IsLessThanOrEqualToType;
use \jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use \jamesiarmes\PhpEws\Type\RestrictionType;
use \jamesiarmes\PhpEws\Type\IsEqualToType;
use \jamesiarmes\PhpEws\Type\FolderIdType;

use \jamesiarmes\PhpEws\Type\ItemIdType;
use \jamesiarmes\PhpEws\Type\RequestAttachmentIdType;

// Replace with the date range you want to search in. As is, this will find all
// messages within the current calendar year.
$start_date = new DateTime('January 1 00:00:00');
$end_date = new DateTime('December 31 23:59:59');
$timezone = 'Eastern Standard Time';

// Set connection information.
$host = '';
$username = '';
$password = '';
$version = Client::VERSION_2010_SP2;

$host = 'webmail.auvix.ru/EWS/exchange.asmx';
$username = 'a.sirotkin@auvix.ru';
$password = '9plkFG_)VN';

$client = new Client($host, $username, $password, $version);
$client->setTimezone($timezone);
/**/
$request = new FindItemType();
$request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();

// Build the start date restriction.
$greater_than = new IsGreaterThanOrEqualToType();
$greater_than->FieldURI = new PathToUnindexedFieldType();
$greater_than->FieldURI->FieldURI = UnindexedFieldURIType::ITEM_DATE_TIME_RECEIVED;
$greater_than->FieldURIOrConstant = new FieldURIOrConstantType();
$greater_than->FieldURIOrConstant->Constant = new ConstantValueType();
$greater_than->FieldURIOrConstant->Constant->Value = $start_date->format('c');

// Build the end date restriction;
$less_than = new IsLessThanOrEqualToType();
$less_than->FieldURI = new PathToUnindexedFieldType();
$less_than->FieldURI->FieldURI = UnindexedFieldURIType::ITEM_DATE_TIME_RECEIVED;
$less_than->FieldURIOrConstant = new FieldURIOrConstantType();
$less_than->FieldURIOrConstant->Constant = new ConstantValueType();
$less_than->FieldURIOrConstant->Constant->Value = $end_date->format('c');

// Build the restriction.
$request->Restriction = new RestrictionType();
$request->Restriction->And = new AndType();
$request->Restriction->And->IsGreaterThanOrEqualTo = $greater_than;
$request->Restriction->And->IsLessThanOrEqualTo = $less_than;

// Return all message properties.
$request->ItemShape = new ItemResponseShapeType();
$request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES; //DEFAULT_PROPERTIES;

$request->Traversal = 'Shallow'; //ItemQueryTraversalType::SHALLOW; //FolderQueryTraversalType::DEEP;

$request->ParentFolderIds->FolderId = new FolderIdType();
$request->ParentFolderIds->FolderId->Id =
'AAMkADRmODQ2NDZjLTAyMjQtNGRlMi05Zjg0LWUzODZhMTU5NDYyZAAuAAAAAABsO5g2Cgi1S6FNb78CG8qQAQCe8a1e3sFSSKGmXPP4zNSFAAAkEVMwAAA=';

$response = $client->FindItem($request);

// Iterate over the results, printing any error messages or message subjects.
$response_messages = $response->ResponseMessages->FindItemResponseMessage;
$i = 0;
foreach ($response_messages as $response_message) {
    // Make sure the request succeeded.
    if ($response_message->ResponseClass != ResponseClassType::SUCCESS) {
        $code = $response_message->ResponseCode;
        $message = $response_message->MessageText;
        fwrite(
            STDERR,
            "Failed to search for messages with \"$code: $message\"\n"
        );
        continue;
    }

    // Iterate over the messages that were found, printing the subject for each.
    $items = $response_message->RootFolder->Items->Message;

    foreach ($items as $item) {
#break;
        $i ++;
        $subject = $item->Subject;
        $message_id = $item->ItemId->Id;
        $body    = $item->Body;
        fwrite(STDOUT, "$subject: $message_id\n");

        $item = new ItemIdType();
        $item->Id = $message_id;
        $request->ItemIds->ItemId[] = $item;

        $response = $client->GetItem($request);

        // Iterate over the results, printing any error messages or receiving
        // attachments.
        $response_messages = $response->ResponseMessages->GetItemResponseMessage;
        foreach ($response_messages as $response_message) {
            // Make sure the request succeeded.
            if ($response_message->ResponseClass != ResponseClassType::SUCCESS) {
                $code = $response_message->ResponseCode;
                $message = $response_message->MessageText;
                fwrite(STDERR, "Failed to get message with \"$code: $message\"\n");
                continue;
            }

            // Iterate over the messages, getting the attachments for each.
            $attachments = array();
            foreach ($response_message->Items->Message as $item) {
                // If there are no attachments for the item, move on to the next
                // message.
                var_dump($item);
                exit;
                if (empty($item->Attachments)) {
                    continue;
                }

                // Iterate over the attachments for the message.
                foreach ($item->Attachments->FileAttachment as $attachment) {
                    $attachments[] = $attachment->AttachmentId->Id;
                }
            }

            // Build the request to get the attachments.
            $request = new GetAttachmentType();
            $request->AttachmentIds = new NonEmptyArrayOfRequestAttachmentIdsType();

            // Iterate over the attachments for the message.
            foreach ($attachments as $attachment_id) {
                $id = new RequestAttachmentIdType();
                $id->Id = $attachment_id;
                $request->AttachmentIds->AttachmentId[] = $id;
            }

            $response = $client->GetAttachment($request);

            // Iterate over the response messages, printing any error messages or
            // saving the attachments.
            $attachment_response_messages = $response->ResponseMessages
            ->GetAttachmentResponseMessage;
            foreach ($attachment_response_messages as $attachment_response_message) {
                // Make sure the request succeeded.
                if ($attachment_response_message->ResponseClass
                    != ResponseClassType::SUCCESS) {
                        $code = $response_message->ResponseCode;
                        $message = $response_message->MessageText;
                        fwrite(
                            STDERR,
                            "Failed to get attachment with \"$code: $message\"\n"
                            );
                        continue;
                    }

                    // Iterate over the file attachments, saving each one.
                    $attachments = $attachment_response_message->Attachments
                    ->FileAttachment;
                    foreach ($attachments as $attachment) {
                        $path = "$file_destination/" . $attachment->Name;
                        file_put_contents($path, $attachment->Content);
                        fwrite(STDOUT, "Created attachment $path\n");
                    }
            }
        }

    }
}
var_dump($i);
exit;
