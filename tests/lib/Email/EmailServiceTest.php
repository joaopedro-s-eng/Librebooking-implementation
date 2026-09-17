<?php

declare(strict_types=1);

require_once(ROOT_DIR . 'lib/Email/EmailService.php');

class EmailServiceTest extends TestBase
{
    public function setUp(): void
    {
        parent::setup();
    }

    public function teardown(): void
    {
        parent::teardown();
    }

    public function testAttachmentsAreClearedBeforeEachSendToPreventLeakingIntoSubsequentEmails()
    {
        $phpMailer = $this->createMock('PHPMailer\PHPMailer\PHPMailer');

        $attachments = [];
        $attachmentsAtSend = [];

        $phpMailer->expects($this->exactly(2))
            ->method('clearAttachments')
            ->willReturnCallback(function () use (&$attachments) {
                $attachments = [];
            });

        $phpMailer->expects($this->exactly(2))
            ->method('addStringAttachment')
            ->willReturnCallback(function ($contents, $fileName) use (&$attachments) {
                $attachments[] = $fileName;
            });

        $phpMailer->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function () use (&$attachments, &$attachmentsAtSend) {
                $attachmentsAtSend[] = $attachments;
                return true;
            });

        $message1 = new FakeEmailMessage();
        $message1->AddStringAttachment('first attachment', 'first.ics');

        $message2 = new FakeEmailMessage();
        $message2->AddStringAttachment('second attachment', 'second.ics');

        $service = new EmailService($phpMailer);
        $service->Send($message1);
        $service->Send($message2);

        $this->assertEquals(
            [['first.ics'], ['second.ics']],
            $attachmentsAtSend,
            'Each send() should see only the attachment for its own message, proving clearAttachments() ran before addStringAttachment() each time.'
        );
    }
}
