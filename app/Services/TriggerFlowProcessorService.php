<?php

namespace App\Services;

use App\Models\EcomTriggerFlowEnrollment;
use App\Models\EcomTriggerFlowTask;
use App\Models\EcomTriggerFlowLog;
use App\Models\EcomTriggerSetting;
use App\Models\EcomTriggerFlow;
use App\Models\ClientAllDatabase;
use App\Models\EcomOrder;
use App\Models\EcomStoreSmtpSetting;
use App\Models\EcomAffiliate;
use App\Models\EcomAffiliateStore;
use App\Models\EcomStoreLogin;
use App\Models\AsEnrollment;
use App\Models\AsCourse;
use App\Models\AxisTag;
use App\Models\ClientsAccessTag;
use App\Models\ClientAccessLogin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class TriggerFlowProcessorService
{
    protected $startTime;
    protected $executionSource;
    protected $processedCount = 0;
    protected $failedCount = 0;
    protected $errors = [];

    /**
     * Process all pending tasks that are ready to execute.
     */
    public function processPendingTasks($source = 'cron')
    {
        $this->startTime = microtime(true);
        $this->executionSource = $source;
        $this->processedCount = 0;
        $this->failedCount = 0;
        $this->errors = [];

        // Update last cron run
        EcomTriggerSetting::updateLastCronRun();

        // Get batch size
        $batchSize = EcomTriggerSetting::getCronBatchSize();

        // Log cron run
        EcomTriggerFlowLog::info(
            EcomTriggerFlowLog::ACTION_CRON_RUN,
            'Cron processing started',
            [
                'executionSource' => $source,
                'logData' => ['batchSize' => $batchSize],
            ]
        );

        try {
            // Get tasks that are ready to execute
            $tasks = EcomTriggerFlowTask::active()
                ->readyToExecute()
                ->whereHas('enrollment', function($q) {
                    $q->where('status', 'active')
                      ->where('deleteStatus', 'active');
                })
                ->orderBy('scheduledAt', 'asc')
                ->orderBy('taskOrder', 'asc')
                ->limit($batchSize)
                ->get();

            foreach ($tasks as $task) {
                $this->processTask($task);
            }

            // Log completion
            $executionTime = microtime(true) - $this->startTime;
            EcomTriggerFlowLog::info(
                EcomTriggerFlowLog::ACTION_CRON_RUN,
                'Cron processing completed',
                [
                    'executionSource' => $source,
                    'executionTime' => $executionTime,
                    'logData' => [
                        'processed' => $this->processedCount,
                        'failed' => $this->failedCount,
                    ],
                ]
            );

        } catch (\Exception $e) {
            Log::error('TriggerFlowProcessor error: ' . $e->getMessage());
            $this->errors[] = $e->getMessage();
        }

        return [
            'processed' => $this->processedCount,
            'failed' => $this->failedCount,
            'errors' => $this->errors,
            'executionTime' => microtime(true) - $this->startTime,
        ];
    }

    /**
     * Process a single task.
     */
    protected function processTask(EcomTriggerFlowTask $task)
    {
        $taskStartTime = microtime(true);

        try {
            // Mark as running
            $task->markRunning();

            EcomTriggerFlowLog::info(
                EcomTriggerFlowLog::ACTION_TASK_STARTED,
                "Processing task: {$task->nodeTypeLabel}",
                [
                    'enrollmentId' => $task->enrollmentId,
                    'taskId' => $task->id,
                    'flowId' => $task->flowId,
                    'nodeType' => $task->nodeType,
                    'nodeLabel' => $task->nodeLabel,
                    'executionSource' => $this->executionSource,
                ]
            );

            // Get enrollment context
            $enrollment = $task->enrollment;
            $contextData = $enrollment->contextData ?? [];

            // Execute based on node type
            $result = $this->executeNodeAction($task, $enrollment, $contextData);

            if ($result['success']) {
                $task->markCompleted($result['data'] ?? null);

                EcomTriggerFlowLog::info(
                    EcomTriggerFlowLog::ACTION_TASK_COMPLETED,
                    "Task completed: {$task->nodeTypeLabel}",
                    [
                        'enrollmentId' => $task->enrollmentId,
                        'taskId' => $task->id,
                        'flowId' => $task->flowId,
                        'nodeType' => $task->nodeType,
                        'executionSource' => $this->executionSource,
                        'executionTime' => microtime(true) - $taskStartTime,
                        'logData' => $result['data'] ?? null,
                    ]
                );

                // Schedule next task(s) if applicable
                $this->scheduleNextTasks($task, $enrollment, $result);

                $this->processedCount++;
            } else {
                $task->markFailed($result['error'] ?? 'Unknown error');

                EcomTriggerFlowLog::error(
                    EcomTriggerFlowLog::ACTION_TASK_FAILED,
                    "Task failed: {$result['error']}",
                    [
                        'enrollmentId' => $task->enrollmentId,
                        'taskId' => $task->id,
                        'flowId' => $task->flowId,
                        'nodeType' => $task->nodeType,
                        'executionSource' => $this->executionSource,
                        'executionTime' => microtime(true) - $taskStartTime,
                    ]
                );

                $this->failedCount++;
                $this->errors[] = "Task {$task->id}: {$result['error']}";
            }

        } catch (\Exception $e) {
            Log::error("Error processing task {$task->id}: " . $e->getMessage());

            $task->markFailed($e->getMessage());

            EcomTriggerFlowLog::error(
                EcomTriggerFlowLog::ACTION_TASK_FAILED,
                "Task exception: {$e->getMessage()}",
                [
                    'enrollmentId' => $task->enrollmentId,
                    'taskId' => $task->id,
                    'flowId' => $task->flowId,
                    'nodeType' => $task->nodeType,
                    'executionSource' => $this->executionSource,
                ]
            );

            $this->failedCount++;
            $this->errors[] = "Task {$task->id}: {$e->getMessage()}";
        }
    }

    /**
     * Execute the action for a specific node type.
     */
    protected function executeNodeAction(EcomTriggerFlowTask $task, EcomTriggerFlowEnrollment $enrollment, array $contextData)
    {
        $nodeData = $task->nodeData ?? [];

        switch ($task->nodeType) {
            // Start nodes (just mark as completed)
            case 'trigger_tag':
            case 'course_access_start':
            case 'course_tag_start':
            case 'product_variant_start':
            case 'special_tag_start':
            case 'order_status_start':
                return ['success' => true, 'data' => ['message' => 'Start node completed']];

            // Delay node - always succeeds, next task will be scheduled
            case 'delay':
                return ['success' => true, 'data' => ['message' => 'Delay completed']];

            // Schedule node
            case 'schedule':
                return ['success' => true, 'data' => ['message' => 'Schedule reached']];

            // Email node
            case 'email':
                return $this->sendEmail($nodeData, $contextData, $enrollment);

            // SMS node
            case 'send_sms':
                return $this->sendSms($nodeData, $contextData, $enrollment);

            // WhatsApp node
            case 'send_whatsapp':
                return $this->sendWhatsApp($nodeData, $contextData, $enrollment);

            // Condition nodes
            case 'if_else':
                return $this->evaluateCondition($nodeData, $contextData, $enrollment);

            // Y Flow (parallel paths)
            case 'y_flow':
                return ['success' => true, 'data' => ['branches' => ['path_a', 'path_b']]];

            // Grant course access
            case 'course_access':
                return $this->grantCourseAccess($nodeData, $contextData, $enrollment);

            // Remove access
            case 'remove_access':
                return $this->removeAccess($nodeData, $contextData, $enrollment);

            // Add as affiliate
            case 'add_as_affiliate':
                return $this->addAsAffiliate($nodeData, $contextData, $enrollment);

            // Grant login access
            case 'add_login_access':
                return $this->grantLoginAccess($nodeData, $contextData, $enrollment);

            // Course subscription
            case 'course_subscription':
                return $this->manageCourseSubscription($nodeData, $contextData, $enrollment);

            // Flow action (add/remove from another flow)
            case 'flow_action':
                return $this->handleFlowAction($nodeData, $contextData, $enrollment);

            // AI add referral
            case 'ai_add_referral':
                return $this->addAiReferral($nodeData, $contextData, $enrollment);

            default:
                return ['success' => false, 'error' => "Unknown node type: {$task->nodeType}"];
        }
    }

    /**
     * Schedule the next task(s) after a task completes.
     */
    protected function scheduleNextTasks(EcomTriggerFlowTask $completedTask, EcomTriggerFlowEnrollment $enrollment, array $result)
    {
        $flow = $enrollment->flow;
        if (!$flow || !$flow->flowData) {
            return;
        }

        $flowData = $flow->flowData;
        $connections = $flowData['connections'] ?? [];
        $nodes = $flowData['nodes'] ?? [];

        // Find connections from this node
        $outgoingConnections = array_filter($connections, function($conn) use ($completedTask) {
            return $conn['source'] === $completedTask->nodeId;
        });

        foreach ($outgoingConnections as $connection) {
            $targetNodeId = $connection['target'];
            $connectionType = $connection['type'] ?? 'default';

            // For condition nodes, only follow the matching branch
            if ($completedTask->nodeType === 'if_else') {
                $conditionResult = $result['data']['conditionResult'] ?? null;
                if ($connectionType === 'yes' && !$conditionResult) continue;
                if ($connectionType === 'no' && $conditionResult) continue;
            }

            // Find the next task
            $nextTask = EcomTriggerFlowTask::active()
                ->forEnrollment($enrollment->id)
                ->where('nodeId', $targetNodeId)
                ->first();

            if ($nextTask && in_array($nextTask->status, ['pending'])) {
                // Check if the next node is a delay
                $nextNode = collect($nodes)->firstWhere('id', $targetNodeId);

                if ($nextNode && $nextNode['type'] === 'delay') {
                    // Calculate delay time
                    $delayData = $nextNode['data'] ?? [];
                    $scheduledAt = $this->calculateDelayTime($delayData);
                    $nextTask->scheduleFor($scheduledAt);
                } elseif ($nextNode && $nextNode['type'] === 'schedule') {
                    // Schedule for specific time
                    $scheduleData = $nextNode['data'] ?? [];
                    $scheduledAt = $this->calculateScheduleTime($scheduleData);
                    $nextTask->scheduleFor($scheduledAt);
                } else {
                    // Mark as ready immediately
                    $nextTask->markReady();
                }
            }
        }
    }

    /**
     * Calculate when a delay task should be executed.
     */
    protected function calculateDelayTime(array $delayData)
    {
        $value = (int) ($delayData['delayValue'] ?? 1);
        $type = $delayData['delayType'] ?? 'days';
        $time = $delayData['delayTime'] ?? null; // Time of day for days

        $now = Carbon::now('Asia/Manila');

        switch ($type) {
            case 'minutes':
                return $now->addMinutes($value);
            case 'hours':
                return $now->addHours($value);
            case 'days':
                $targetDate = $now->addDays($value);
                if ($time) {
                    list($hour, $minute) = explode(':', $time);
                    $targetDate->setTime((int) $hour, (int) $minute, 0);
                }
                return $targetDate;
            case 'weeks':
                return $now->addWeeks($value);
            default:
                return $now->addDays($value);
        }
    }

    /**
     * Calculate when a scheduled task should be executed.
     */
    protected function calculateScheduleTime(array $scheduleData)
    {
        $date = $scheduleData['scheduleDate'] ?? null;
        $time = $scheduleData['scheduleTime'] ?? '09:00';

        if (!$date) {
            return Carbon::now('Asia/Manila')->addDay();
        }

        $dateTime = Carbon::parse($date . ' ' . $time, 'Asia/Manila');

        // If the time has passed, move to next occurrence
        if ($dateTime->isPast()) {
            $dateTime->addDay();
        }

        return $dateTime;
    }

    /**
     * Send email action using store's SMTP settings.
     */
    protected function sendEmail(array $nodeData, array $contextData, EcomTriggerFlowEnrollment $enrollment)
    {
        $subject = $nodeData['subject'] ?? 'No Subject';
        $body = $nodeData['body'] ?? '';

        // Get recipient email
        $toEmail = $this->getContextValue($contextData, 'client_email');

        if (!$toEmail) {
            return ['success' => false, 'error' => 'No recipient email found'];
        }

        // Replace merge tags
        $subject = $this->replaceMergeTags($subject, $contextData);
        $body = $this->replaceMergeTags($body, $contextData);

        // Get store's SMTP settings
        $flow = $enrollment->flow;
        if (!$flow || !$flow->storeId) {
            return ['success' => false, 'error' => 'No store configured for this flow'];
        }

        $smtpSettings = EcomStoreSmtpSetting::where('storeId', $flow->storeId)
            ->where('deleteStatus', 1)
            ->where('isActive', true)
            ->first();

        if (!$smtpSettings || !$smtpSettings->isConfigured()) {
            return ['success' => false, 'error' => 'SMTP not configured for store'];
        }

        try {
            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtpSettings->smtpHost;
            $mail->Port = $smtpSettings->smtpPort;
            $mail->CharSet = 'UTF-8';

            if ($smtpSettings->smtpUsername) {
                $mail->SMTPAuth = true;
                $mail->Username = $smtpSettings->smtpUsername;
                $mail->Password = $smtpSettings->decrypted_password;
            }

            if ($smtpSettings->smtpEncryption !== 'none') {
                $mail->SMTPSecure = $smtpSettings->smtpEncryption === 'ssl'
                    ? PHPMailer::ENCRYPTION_SMTPS
                    : PHPMailer::ENCRYPTION_STARTTLS;
            }

            // Recipients
            $mail->setFrom($smtpSettings->smtpFromEmail, $smtpSettings->smtpFromName);
            $mail->addAddress($toEmail);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();

            Log::info("Trigger email sent successfully", [
                'to' => $toEmail,
                'subject' => $subject,
                'store_id' => $flow->storeId,
            ]);

            return [
                'success' => true,
                'data' => [
                    'to' => $toEmail,
                    'subject' => $subject,
                    'from' => $smtpSettings->smtpFromEmail,
                    'sent_at' => now()->format('Y-m-d H:i:s'),
                ]
            ];

        } catch (PHPMailerException $e) {
            Log::error("Trigger email failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Email sending failed: ' . $e->getMessage()];
        } catch (\Exception $e) {
            Log::error("Trigger email exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Email sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Send SMS action.
     * Note: Requires SMS API configuration (e.g., Semaphore, Twilio).
     * Currently logs the message and marks as completed.
     */
    protected function sendSms(array $nodeData, array $contextData, EcomTriggerFlowEnrollment $enrollment)
    {
        $message = $nodeData['message'] ?? '';
        $phone = $this->getContextValue($contextData, 'client_phone');

        if (!$phone) {
            return ['success' => false, 'error' => 'No phone number found'];
        }

        // Format Philippine phone number
        $phone = $this->formatPhoneNumber($phone);
        $message = $this->replaceMergeTags($message, $contextData);

        try {
            // TODO: Implement actual SMS sending via API (Semaphore, Twilio, etc.)
            // For now, log and mark as completed
            Log::warning("SMS API not configured. Would send to: {$phone}, Message: " . substr($message, 0, 50) . "...");

            return [
                'success' => true,
                'data' => [
                    'to' => $phone,
                    'message' => $message,
                    'status' => 'logged', // Change to 'sent' when API is implemented
                    'note' => 'SMS API not yet configured',
                    'logged_at' => now()->format('Y-m-d H:i:s'),
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'SMS sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Format phone number for Philippine SMS.
     */
    protected function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to Philippine format if needed
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '9') {
            $phone = '63' . $phone;
        } elseif (strlen($phone) === 11 && substr($phone, 0, 2) === '09') {
            $phone = '63' . substr($phone, 1);
        } elseif (substr($phone, 0, 1) === '0') {
            $phone = '63' . substr($phone, 1);
        }

        return '+' . $phone;
    }

    /**
     * Send WhatsApp action.
     * Note: Requires WhatsApp Business API configuration.
     * Currently logs the message and marks as completed.
     */
    protected function sendWhatsApp(array $nodeData, array $contextData, EcomTriggerFlowEnrollment $enrollment)
    {
        $message = $nodeData['message'] ?? '';
        $phone = $this->getContextValue($contextData, 'client_phone');

        if (!$phone) {
            return ['success' => false, 'error' => 'No phone number found'];
        }

        // Format phone number
        $phone = $this->formatPhoneNumber($phone);
        $message = $this->replaceMergeTags($message, $contextData);

        try {
            // TODO: Implement actual WhatsApp sending via WhatsApp Business API
            // Popular options: Twilio WhatsApp, Meta Cloud API, WATI, etc.
            Log::warning("WhatsApp API not configured. Would send to: {$phone}, Message: " . substr($message, 0, 50) . "...");

            return [
                'success' => true,
                'data' => [
                    'to' => $phone,
                    'message' => $message,
                    'status' => 'logged', // Change to 'sent' when API is implemented
                    'note' => 'WhatsApp API not yet configured',
                    'logged_at' => now()->format('Y-m-d H:i:s'),
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'WhatsApp sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Evaluate if/else condition.
     */
    protected function evaluateCondition(array $nodeData, array $contextData, EcomTriggerFlowEnrollment $enrollment)
    {
        $conditionType = $nodeData['conditionType'] ?? '';
        $conditionValue = $nodeData['conditionValue'] ?? null;

        $result = false;

        switch ($conditionType) {
            case 'has_tag':
                // Check if client has specific tag
                $clientTags = $contextData['client_tags'] ?? [];
                $result = in_array($conditionValue, $clientTags);
                break;

            case 'not_has_tag':
                $clientTags = $contextData['client_tags'] ?? [];
                $result = !in_array($conditionValue, $clientTags);
                break;

            case 'order_total':
                $orderTotal = (float) ($contextData['order_total'] ?? 0);
                $operator = $nodeData['operator'] ?? '>=';
                $compareValue = (float) $conditionValue;
                $result = $this->compareValues($orderTotal, $operator, $compareValue);
                break;

            case 'has_course_access':
                // Check if client has course access
                // TODO: Implement actual check
                $result = false;
                break;

            case 'is_affiliate':
                // Check if client is an affiliate
                // TODO: Implement actual check
                $result = false;
                break;

            case 'client_province':
                $clientProvince = $contextData['client_province'] ?? '';
                $result = strtolower($clientProvince) === strtolower($conditionValue);
                break;

            case 'payment_method':
                $paymentMethod = $contextData['payment_method'] ?? '';
                $result = strtolower($paymentMethod) === strtolower($conditionValue);
                break;

            default:
                $result = false;
        }

        return [
            'success' => true,
            'data' => [
                'conditionType' => $conditionType,
                'conditionResult' => $result,
            ]
        ];
    }

    /**
     * Compare values with operator.
     */
    protected function compareValues($value1, $operator, $value2)
    {
        return match($operator) {
            '>' => $value1 > $value2,
            '>=' => $value1 >= $value2,
            '<' => $value1 < $value2,
            '<=' => $value1 <= $value2,
            '=', '==' => $value1 == $value2,
            '!=' => $value1 != $value2,
            default => false,
        };
    }

    /**
     * Grant course access by adding an access tag to the client.
     */
    protected function grantCourseAccess(array $nodeData, array $contextData, EcomTriggerFlowEnrollment $enrollment)
    {
        $tagId = $nodeData['tagId'] ?? null;
        $tagName = $nodeData['tagName'] ?? 'Unknown';
        $clientId = $enrollment->clientId;

        if (!$tagId) {
            return ['success' => false, 'error' => 'No tag selected'];
        }

        if (!$clientId) {
            // Try to get client from context
            $clientId = $contextData['client_id'] ?? $contextData['clientId'] ?? null;
        }

        if (!$clientId) {
            return ['success' => false, 'error' => 'No client found for access grant'];
        }

        try {
            // Check if client already has this tag
            $existingAccess = DB::table('clients_access_tags')
                ->where('clientId', $clientId)
                ->where('tagId', $tagId)
                ->where('deleteStatus', 1)
                ->first();

            if ($existingAccess) {
                Log::info("Client already has access tag", [
                    'client_id' => $clientId,
                    'tag_id' => $tagId,
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'tagId' => $tagId,
                        'tagName' => $tagName,
                        'status' => 'already_exists',
                        'granted_at' => now()->format('Y-m-d H:i:s'),
                    ]
                ];
            }

            // Grant the access tag
            DB::table('clients_access_tags')->insert([
                'clientId' => $clientId,
                'tagId' => $tagId,
                'deleteStatus' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("Course access granted", [
                'client_id' => $clientId,
                'tag_id' => $tagId,
                'tag_name' => $tagName,
            ]);

            return [
                'success' => true,
                'data' => [
                    'tagId' => $tagId,
                    'tagName' => $tagName,
                    'clientId' => $clientId,
                    'status' => 'granted',
                    'granted_at' => now()->format('Y-m-d H:i:s'),
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Failed to grant course access: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to grant access: ' . $e->getMessage()];
        }
    }

    /**
     * Remove access by removing an access tag from the client.
     */
    protected function removeAccess(array $nodeData, array $contextData, EcomTriggerFlowEnrollment $enrollment)
    {
        $tagId = $nodeData['tagId'] ?? null;
        $tagName = $nodeData['tagName'] ?? 'Unknown';
        $clientId = $enrollment->clientId;

        if (!$tagId) {
            return ['success' => false, 'error' => 'No tag selected'];
        }

        if (!$clientId) {
            $clientId = $contextData['client_id'] ?? $contextData['clientId'] ?? null;
        }

        if (!$clientId) {
            return ['success' => false, 'error' => 'No client found for access removal'];
        }

        try {
            // Soft delete the access tag
            $affected = DB::table('clients_access_tags')
                ->where('clientId', $clientId)
                ->where('tagId', $tagId)
                ->where('deleteStatus', 1)
                ->update([
                    'deleteStatus' => 0,
                    'updated_at' => now(),
                ]);

            Log::info("Access removed", [
                'client_id' => $clientId,
                'tag_id' => $tagId,
                'tag_name' => $tagName,
                'affected' => $affected,
            ]);

            return [
                'success' => true,
                'data' => [
                    'tagId' => $tagId,
                    'tagName' => $tagName,
                    'clientId' => $clientId,
                    'status' => $affected > 0 ? 'removed' : 'not_found',
                    'removed_at' => now()->format('Y-m-d H:i:s'),
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Failed to remove access: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to remove access: ' . $e->getMessage()];
        }
    }

    /**
     * Add as affiliate.
     */
    protected function addAsAffiliate(array $nodeData, array $contextData, EcomTriggerFlowEnrollment $enrollment)
    {
        $storeId = $nodeData['storeId'] ?? $enrollment->flow->storeId ?? null;
        $storeName = $nodeData['storeName'] ?? 'Unknown';

        if (!$storeId) {
            return ['success' => false, 'error' => 'No store selected'];
        }

        // Get client details from context or enrollment
        $clientId = $enrollment->clientId ?? $contextData['client_id'] ?? $contextData['clientId'] ?? null;
        $firstName = $contextData['client_first_name'] ?? $contextData['clientFirstName'] ?? '';
        $lastName = $contextData['client_last_name'] ?? $contextData['clientLastName'] ?? '';
        $email = $contextData['client_email'] ?? $contextData['clientEmail'] ?? '';
        $phone = $contextData['client_phone'] ?? $contextData['clientPhone'] ?? '';

        if (!$firstName && isset($contextData['client_name'])) {
            $nameParts = explode(' ', $contextData['client_name'], 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
        }

        try {
            // Check if affiliate already exists for this client
            $existingAffiliate = null;
            if ($clientId) {
                $existingAffiliate = EcomAffiliate::active()
                    ->where('clientId', $clientId)
                    ->first();
            }

            // If no affiliate by clientId, check by email
            if (!$existingAffiliate && $email) {
                $existingAffiliate = EcomAffiliate::active()
                    ->where('emailAddress', $email)
                    ->first();
            }

            if ($existingAffiliate) {
                // Check if already linked to this store
                $storeLink = EcomAffiliateStore::where('affiliateId', $existingAffiliate->id)
                    ->where('storeId', $storeId)
                    ->where('deleteStatus', 1)
                    ->first();

                if ($storeLink) {
                    Log::info("Affiliate already linked to store", [
                        'affiliate_id' => $existingAffiliate->id,
                        'store_id' => $storeId,
                    ]);

                    return [
                        'success' => true,
                        'data' => [
                            'affiliateId' => $existingAffiliate->id,
                            'storeId' => $storeId,
                            'status' => 'already_exists',
                            'created_at' => now()->format('Y-m-d H:i:s'),
                        ]
                    ];
                }

                // Link existing affiliate to new store
                EcomAffiliateStore::create([
                    'affiliateId' => $existingAffiliate->id,
                    'storeId' => $storeId,
                    'totalEarnings' => 0,
                    'totalPending' => 0,
                    'deleteStatus' => 1,
                ]);

                Log::info("Linked existing affiliate to store", [
                    'affiliate_id' => $existingAffiliate->id,
                    'store_id' => $storeId,
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'affiliateId' => $existingAffiliate->id,
                        'storeId' => $storeId,
                        'status' => 'linked_to_store',
                        'created_at' => now()->format('Y-m-d H:i:s'),
                    ]
                ];
            }

            // Create new affiliate
            $affiliate = EcomAffiliate::create([
                'clientId' => $clientId,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'phoneNumber' => $phone,
                'emailAddress' => $email,
                'accountStatus' => 'active',
                'deleteStatus' => 1,
            ]);

            // Link to store
            EcomAffiliateStore::create([
                'affiliateId' => $affiliate->id,
                'storeId' => $storeId,
                'totalEarnings' => 0,
                'totalPending' => 0,
                'deleteStatus' => 1,
            ]);

            Log::info("Created new affiliate", [
                'affiliate_id' => $affiliate->id,
                'store_id' => $storeId,
                'email' => $email,
            ]);

            return [
                'success' => true,
                'data' => [
                    'affiliateId' => $affiliate->id,
                    'storeId' => $storeId,
                    'storeName' => $storeName,
                    'status' => 'created',
                    'created_at' => now()->format('Y-m-d H:i:s'),
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Failed to add affiliate: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to add affiliate: ' . $e->getMessage()];
        }
    }

    /**
     * Grant login access to a store.
     */
    protected function grantLoginAccess(array $nodeData, array $contextData, EcomTriggerFlowEnrollment $enrollment)
    {
        $storeId = $nodeData['storeId'] ?? $enrollment->flow->storeId ?? null;
        $storeName = $nodeData['storeName'] ?? 'Unknown';

        if (!$storeId) {
            return ['success' => false, 'error' => 'No store selected'];
        }

        // Get client details from context or enrollment
        $firstName = $contextData['client_first_name'] ?? $contextData['clientFirstName'] ?? '';
        $lastName = $contextData['client_last_name'] ?? $contextData['clientLastName'] ?? '';
        $email = $contextData['client_email'] ?? $contextData['clientEmail'] ?? '';
        $phone = $contextData['client_phone'] ?? $contextData['clientPhone'] ?? '';

        if (!$firstName && isset($contextData['client_name'])) {
            $nameParts = explode(' ', $contextData['client_name'], 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
        }

        if (!$email && !$phone) {
            return ['success' => false, 'error' => 'No email or phone found for login access'];
        }

        try {
            // Check if login already exists (productStore stores store NAME, not ID)
            $existingLogin = ClientAccessLogin::active()
                ->where('productStore', $storeName)
                ->where(function ($q) use ($email, $phone) {
                    if ($email) {
                        $q->where('clientEmailAddress', $email);
                    }
                    if ($phone) {
                        $q->orWhere('clientPhoneNumber', $phone);
                    }
                })
                ->first();

            if ($existingLogin) {
                // Ensure it's active
                if (!$existingLogin->isActive) {
                    $existingLogin->update(['isActive' => true]);
                }

                // Get existing password or generate new one if empty
                $loginPassword = $existingLogin->clientPassword;
                if (empty($loginPassword)) {
                    $loginPassword = $this->generateRandomPassword();
                    $existingLogin->update(['clientPassword' => $loginPassword]);
                }

                Log::info("Login access already exists", [
                    'login_id' => $existingLogin->id,
                    'store_id' => $storeId,
                ]);

                // Update enrollment context with login credentials
                $enrollment->contextData = array_merge($enrollment->contextData ?? [], [
                    'login_email' => $existingLogin->clientEmailAddress,
                    'login_password' => $loginPassword,
                    'login_store_name' => $storeName,
                ]);
                $enrollment->save();

                return [
                    'success' => true,
                    'data' => [
                        'loginId' => $existingLogin->id,
                        'storeId' => $storeId,
                        'storeName' => $storeName,
                        'status' => 'already_exists',
                        'granted_at' => now()->format('Y-m-d H:i:s'),
                        'login_email' => $existingLogin->clientEmailAddress,
                        'login_password' => $loginPassword,
                    ]
                ];
            }

            // Generate a random password
            $plainPassword = $this->generateRandomPassword();

            // Create new login access (productStore stores store NAME, not ID)
            $login = ClientAccessLogin::create([
                'productStore' => $storeName,
                'clientFirstName' => $firstName,
                'clientMiddleName' => '',
                'clientLastName' => $lastName,
                'clientPhoneNumber' => $phone,
                'clientEmailAddress' => $email,
                'clientPassword' => $plainPassword, // Store plain for now (can be hashed later)
                'isActive' => true,
                'deleteStatus' => 1,
            ]);

            Log::info("Login access granted", [
                'login_id' => $login->id,
                'store_id' => $storeId,
                'email' => $email,
            ]);

            // Update enrollment context with login credentials for use in subsequent nodes (e.g., email)
            $enrollment->contextData = array_merge($enrollment->contextData ?? [], [
                'login_email' => $email,
                'login_password' => $plainPassword,
                'login_store_name' => $storeName,
            ]);
            $enrollment->save();

            return [
                'success' => true,
                'data' => [
                    'loginId' => $login->id,
                    'storeId' => $storeId,
                    'storeName' => $storeName,
                    'status' => 'granted',
                    'granted_at' => now()->format('Y-m-d H:i:s'),
                    'login_email' => $email,
                    'login_password' => $plainPassword,
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Failed to grant login access: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to grant login: ' . $e->getMessage()];
        }
    }

    /**
     * Manage course subscription.
     */
    protected function manageCourseSubscription(array $nodeData, array $contextData, EcomTriggerFlowEnrollment $enrollment)
    {
        $action = $nodeData['action'] ?? 'add';
        $courseId = $nodeData['courseId'] ?? null;
        $courseName = $nodeData['courseName'] ?? 'Unknown';
        $durationDays = $nodeData['durationDays'] ?? null;

        if (!$courseId) {
            return ['success' => false, 'error' => 'No course selected'];
        }

        try {
            // TODO: Implement actual subscription management
            Log::info("Course subscription: Action={$action}, Course={$courseName}, Client={$enrollment->clientId}");

            return [
                'success' => true,
                'data' => [
                    'action' => $action,
                    'courseId' => $courseId,
                    'courseName' => $courseName,
                    'durationDays' => $durationDays,
                    'processed_at' => now()->format('Y-m-d H:i:s'),
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to manage subscription: ' . $e->getMessage()];
        }
    }

    /**
     * Handle flow action (add/remove from another flow).
     */
    protected function handleFlowAction(array $nodeData, array $contextData, EcomTriggerFlowEnrollment $enrollment)
    {
        $action = $nodeData['action'] ?? 'add';
        $flowId = $nodeData['flowId'] ?? null;
        $flowName = $nodeData['flowName'] ?? 'Unknown';

        if (!$flowId) {
            return ['success' => false, 'error' => 'No flow selected'];
        }

        try {
            if ($action === 'add') {
                // Enroll client in the specified flow
                $this->enrollInFlow($flowId, $enrollment->clientId, $enrollment->orderId, $contextData);
            } else {
                // Cancel any active enrollments in the specified flow
                EcomTriggerFlowEnrollment::active()
                    ->running()
                    ->forFlow($flowId)
                    ->forClient($enrollment->clientId)
                    ->get()
                    ->each(function($e) {
                        $e->cancel(null, 'Cancelled by flow action');
                    });
            }

            return [
                'success' => true,
                'data' => [
                    'action' => $action,
                    'flowId' => $flowId,
                    'flowName' => $flowName,
                    'processed_at' => now()->format('Y-m-d H:i:s'),
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to handle flow action: ' . $e->getMessage()];
        }
    }

    /**
     * Add AI referral.
     */
    protected function addAiReferral(array $nodeData, array $contextData, EcomTriggerFlowEnrollment $enrollment)
    {
        try {
            // TODO: Implement AI referral logic
            Log::info("AI Add Referral: Client={$enrollment->clientId}");

            return [
                'success' => true,
                'data' => [
                    'processed_at' => now()->format('Y-m-d H:i:s'),
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to add AI referral: ' . $e->getMessage()];
        }
    }

    /**
     * Enroll a client in a flow.
     */
    public function enrollInFlow($flowId, $clientId = null, $orderId = null, $contextData = [], $source = 'manual', $createdBy = null)
    {
        $flow = EcomTriggerFlow::active()->enabled()->find($flowId);

        if (!$flow) {
            throw new \Exception("Flow not found or inactive");
        }

        $flowData = $flow->flowData;
        $nodes = $flowData['nodes'] ?? [];
        $connections = $flowData['connections'] ?? [];

        if (empty($nodes)) {
            throw new \Exception("Flow has no nodes");
        }

        // Create enrollment
        $enrollment = EcomTriggerFlowEnrollment::create([
            'flowId' => $flowId,
            'clientId' => $clientId,
            'orderId' => $orderId,
            'triggerSource' => $source,
            'contextData' => $contextData,
            'status' => 'active',
            'totalTasks' => count($nodes),
            'completedTasks' => 0,
            'currentTaskOrder' => 0,
            'startedAt' => now(),
            'createdBy' => $createdBy,
            'deleteStatus' => 'active',
        ]);

        // Build task order using BFS
        $taskOrder = $this->buildTaskOrder($nodes, $connections);

        // Create tasks for each node
        foreach ($taskOrder as $index => $nodeId) {
            $node = collect($nodes)->firstWhere('id', $nodeId);

            if (!$node) continue;

            $task = EcomTriggerFlowTask::create([
                'enrollmentId' => $enrollment->id,
                'flowId' => $flowId,
                'nodeId' => $node['id'],
                'nodeType' => $node['type'],
                'nodeLabel' => EcomTriggerFlowTask::$nodeTypeLabels[$node['type']] ?? $node['type'],
                'nodeData' => $node['data'] ?? [],
                'taskOrder' => $index + 1,
                'status' => $index === 0 ? 'ready' : 'pending', // First task is ready
                'scheduledAt' => $index === 0 ? now() : null,
                'deleteStatus' => 'active',
            ]);
        }

        // Log enrollment
        EcomTriggerFlowLog::info(
            EcomTriggerFlowLog::ACTION_ENROLLMENT_CREATED,
            'Enrollment created',
            [
                'enrollmentId' => $enrollment->id,
                'flowId' => $flowId,
                'executedBy' => $createdBy,
                'executionSource' => $source,
                'logData' => [
                    'clientId' => $clientId,
                    'orderId' => $orderId,
                    'totalTasks' => count($nodes),
                ],
            ]
        );

        return $enrollment;
    }

    /**
     * Build task order using BFS from start nodes.
     */
    protected function buildTaskOrder(array $nodes, array $connections)
    {
        $startTypes = ['trigger_tag', 'course_access_start', 'course_tag_start', 'product_variant_start', 'special_tag_start', 'order_status_start'];

        // Find start nodes
        $incomingMap = [];
        foreach ($nodes as $node) {
            $incomingMap[$node['id']] = [];
        }
        foreach ($connections as $conn) {
            if (isset($incomingMap[$conn['target']])) {
                $incomingMap[$conn['target']][] = $conn['source'];
            }
        }

        $startNodes = [];
        foreach ($nodes as $node) {
            if (empty($incomingMap[$node['id']]) || in_array($node['type'], $startTypes)) {
                $startNodes[] = $node['id'];
            }
        }

        if (empty($startNodes)) {
            $startNodes = [$nodes[0]['id']];
        }

        // Build outgoing map
        $outgoingMap = [];
        foreach ($nodes as $node) {
            $outgoingMap[$node['id']] = [];
        }
        foreach ($connections as $conn) {
            if (isset($outgoingMap[$conn['source']])) {
                $outgoingMap[$conn['source']][] = $conn['target'];
            }
        }

        // BFS to get order
        $order = [];
        $visited = [];
        $queue = $startNodes;

        while (!empty($queue)) {
            $nodeId = array_shift($queue);

            if (isset($visited[$nodeId])) continue;
            $visited[$nodeId] = true;
            $order[] = $nodeId;

            foreach ($outgoingMap[$nodeId] ?? [] as $targetId) {
                if (!isset($visited[$targetId])) {
                    $queue[] = $targetId;
                }
            }
        }

        // Add any unvisited nodes at the end
        foreach ($nodes as $node) {
            if (!isset($visited[$node['id']])) {
                $order[] = $node['id'];
            }
        }

        return $order;
    }

    /**
     * Get value from context data.
     */
    protected function getContextValue(array $contextData, $key)
    {
        // Map context keys
        $keyMap = [
            'client_email' => ['client_email', 'clientEmail', 'email'],
            'client_phone' => ['client_phone', 'clientPhone', 'phone'],
            'client_name' => ['client_name', 'clientName', 'name'],
            'order_total' => ['order_total', 'orderTotal', 'total'],
            'client_province' => ['client_province', 'clientProvince', 'province'],
            'payment_method' => ['payment_method', 'paymentMethod'],
        ];

        $keys = $keyMap[$key] ?? [$key];

        foreach ($keys as $k) {
            if (isset($contextData[$k])) {
                return $contextData[$k];
            }
        }

        return null;
    }

    /**
     * Replace merge tags in content.
     */
    protected function replaceMergeTags($content, array $contextData)
    {
        $replacements = [
            '{{client_name}}' => $contextData['client_name'] ?? $contextData['clientName'] ?? '',
            '{{client_first_name}}' => $contextData['client_first_name'] ?? explode(' ', $contextData['client_name'] ?? '')[0] ?? '',
            '{{client_email}}' => $contextData['client_email'] ?? $contextData['clientEmail'] ?? '',
            '{{client_phone}}' => $contextData['client_phone'] ?? $contextData['clientPhone'] ?? '',
            '{{order_number}}' => $contextData['order_number'] ?? $contextData['orderNumber'] ?? '',
            // Login access merge tags
            '{{login_email}}' => $contextData['login_email'] ?? $contextData['client_email'] ?? $contextData['clientEmail'] ?? '',
            '{{login_password}}' => $contextData['login_password'] ?? '',
            '{{login_store_name}}' => $contextData['login_store_name'] ?? $contextData['store_name'] ?? '',
            '{{order_total}}' => $contextData['order_total'] ?? $contextData['orderTotal'] ?? '',
            '{{product_name}}' => $contextData['product_name'] ?? $contextData['productName'] ?? '',
            '{{variant_name}}' => $contextData['variant_name'] ?? $contextData['variantName'] ?? '',
            '{{store_name}}' => $contextData['store_name'] ?? $contextData['storeName'] ?? '',
            '{{purchase_date}}' => $contextData['purchase_date'] ?? now()->format('M j, Y'),
            // Payment/Invoice merge tags
            '{{invoice_url}}' => $contextData['invoice_url'] ?? '',
            '{{invoice_number}}' => $contextData['invoice_number'] ?? '',
            '{{payment_amount}}' => $contextData['payment_amount'] ?? $contextData['amountVerified'] ?? '',
            '{{payment_method}}' => $contextData['payment_method'] ?? '',
            '{{payment_date}}' => $contextData['payment_date'] ?? now()->format('M j, Y'),
            // Add more as needed
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Trigger flows for a specific event.
     *
     * @param string $eventType The type of event (e.g., 'order_created', 'order_status_changed', 'product_purchased')
     * @param array $eventData The event data containing clientId, orderId, storeId, etc.
     * @param int|null $createdBy The user ID who triggered this event
     * @return array List of enrollments created
     */
    public function triggerFlowsForEvent($eventType, array $eventData, $createdBy = null)
    {
        $enrollments = [];

        // Map event types to flow start node types
        $eventToNodeType = [
            'order_created' => 'product_variant_start',
            'order_status_changed' => 'order_status_start',
            'trigger_tag_applied' => 'trigger_tag',
            'course_access_granted' => 'course_access_start',
            'payment_verified' => 'product_variant_start', // Uses product_variant for payment flows
            'payment_rejected' => 'product_variant_start', // Uses product_variant for payment rejection flows
        ];

        $startNodeType = $eventToNodeType[$eventType] ?? null;

        if (!$startNodeType) {
            Log::warning("Unknown event type: {$eventType}");
            return $enrollments;
        }

        // Find all active/enabled flows with matching store
        $query = EcomTriggerFlow::active()->enabled();

        // For payment events, only look at 'payments' type flows
        if (in_array($eventType, ['payment_verified', 'payment_rejected'])) {
            $query->where('flowType', 'payments');
        }

        // Filter by store if provided
        if (isset($eventData['storeId'])) {
            $query->where('storeId', $eventData['storeId']);
        }

        $flows = $query->get();

        foreach ($flows as $flow) {
            $flowData = $flow->flowData;
            $nodes = $flowData['nodes'] ?? [];

            // Check if flow has a matching start node
            $matchingStartNode = null;
            foreach ($nodes as $node) {
                if ($node['type'] === $startNodeType) {
                    // Additional matching based on node data
                    if ($this->nodeMatchesEvent($node, $eventType, $eventData)) {
                        $matchingStartNode = $node;
                        break;
                    }
                }
            }

            if (!$matchingStartNode) {
                continue;
            }

            // Build context data
            $contextData = $this->buildContextData($eventData);

            try {
                $enrollment = $this->enrollInFlow(
                    $flow->id,
                    $eventData['clientId'] ?? null,
                    $eventData['orderId'] ?? null,
                    $contextData,
                    $eventType,
                    $createdBy
                );
                $enrollments[] = $enrollment;

                Log::info("Flow triggered for event", [
                    'event' => $eventType,
                    'flowId' => $flow->id,
                    'enrollmentId' => $enrollment->id,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to trigger flow: " . $e->getMessage(), [
                    'event' => $eventType,
                    'flowId' => $flow->id,
                ]);
            }
        }

        return $enrollments;
    }

    /**
     * Check if a start node matches the event criteria.
     */
    protected function nodeMatchesEvent(array $node, $eventType, array $eventData)
    {
        $nodeData = $node['data'] ?? [];

        switch ($eventType) {
            case 'order_created':
            case 'product_purchased':
            case 'payment_verified':
            case 'payment_rejected':
                // Match by product/variant if specified in node
                $nodeProductId = $nodeData['productId'] ?? null;
                $nodeVariantId = $nodeData['variantId'] ?? null;

                if ($nodeProductId && isset($eventData['productId'])) {
                    if ($nodeProductId != $eventData['productId']) {
                        return false;
                    }
                }
                if ($nodeVariantId && isset($eventData['variantId'])) {
                    if ($nodeVariantId != $eventData['variantId']) {
                        return false;
                    }
                }
                return true;

            case 'order_status_changed':
                // Match by status transition
                $nodeFromStatus = $nodeData['fromStatus'] ?? null;
                $nodeToStatus = $nodeData['toStatus'] ?? null;

                if ($nodeToStatus && isset($eventData['newStatus'])) {
                    if ($nodeToStatus != $eventData['newStatus']) {
                        return false;
                    }
                }
                if ($nodeFromStatus && isset($eventData['oldStatus'])) {
                    if ($nodeFromStatus != $eventData['oldStatus']) {
                        return false;
                    }
                }
                return true;

            case 'trigger_tag_applied':
                // Match by trigger tag
                $nodeTagId = $nodeData['tagId'] ?? null;
                if ($nodeTagId && isset($eventData['tagId'])) {
                    return $nodeTagId == $eventData['tagId'];
                }
                return true;

            case 'course_access_granted':
                // Match by course/tag
                $nodeCourseId = $nodeData['courseId'] ?? null;
                $nodeTagId = $nodeData['tagId'] ?? null;

                if ($nodeCourseId && isset($eventData['courseId'])) {
                    return $nodeCourseId == $eventData['courseId'];
                }
                if ($nodeTagId && isset($eventData['tagId'])) {
                    return $nodeTagId == $eventData['tagId'];
                }
                return true;

            default:
                return true;
        }
    }

    /**
     * Build context data from event data.
     */
    protected function buildContextData(array $eventData)
    {
        $context = [];
        $clientInfoFromOrder = false;

        // Client data - try from clientId first
        if (isset($eventData['clientId'])) {
            $client = ClientAllDatabase::find($eventData['clientId']);
            if ($client) {
                $context['client_id'] = $client->id;
                $context['client_name'] = $client->fullName ?? trim(($client->clientFirstName ?? '') . ' ' . ($client->clientLastName ?? ''));
                $context['client_first_name'] = $client->clientFirstName ?? '';
                $context['client_last_name'] = $client->clientLastName ?? '';
                $context['client_email'] = $client->clientEmailAddress ?? '';
                $context['client_phone'] = $client->clientPhoneNumber ?? '';
            }
        }

        // Order data
        if (isset($eventData['orderId'])) {
            $order = EcomOrder::find($eventData['orderId']);
            if ($order) {
                $context['order_id'] = $order->id;
                $context['order_number'] = $order->orderNumber ?? $order->id;
                $context['order_total'] = $order->grandTotal ?? 0;
                $context['order_subtotal'] = $order->subtotal ?? 0;
                $context['order_status'] = $order->orderStatus ?? '';
                $context['payment_method'] = $order->paymentMethod ?? '';
                $context['purchase_date'] = $order->created_at ? $order->created_at->format('M j, Y') : now()->format('M j, Y');

                // Shipping info
                $context['shipping_address'] = $order->shippingAddress ?? '';
                $context['shipping_city'] = $order->shippingCity ?? '';
                $context['shipping_province'] = $order->shippingProvince ?? '';
                $context['client_province'] = $order->shippingProvince ?? '';

                // If client info not set from clientId, get from order fields
                if (!isset($context['client_name']) || empty($context['client_name']) || $context['client_name'] === ' ') {
                    $firstName = $order->clientFirstName ?? '';
                    $lastName = $order->clientLastName ?? '';
                    $context['client_name'] = trim($firstName . ' ' . $lastName);
                    $context['client_first_name'] = $firstName;
                    $context['client_last_name'] = $lastName;
                    $context['client_email'] = $order->clientEmail ?? '';
                    $context['client_phone'] = $order->clientPhone ?? '';
                }
            }
        }

        // Store data
        if (isset($eventData['storeId'])) {
            $store = \App\Models\EcomProductStore::find($eventData['storeId']);
            if ($store) {
                $context['store_id'] = $store->id;
                $context['store_name'] = $store->storeName ?? '';
            }
        }

        // Product data
        if (isset($eventData['productId'])) {
            $product = \App\Models\EcomProduct::find($eventData['productId']);
            if ($product) {
                $context['product_id'] = $product->id;
                $context['product_name'] = $product->productName ?? '';
            }
        }

        // Variant data
        if (isset($eventData['variantId'])) {
            $variant = \App\Models\EcomProductVariant::find($eventData['variantId']);
            if ($variant) {
                $context['variant_id'] = $variant->id;
                $context['variant_name'] = $variant->variantName ?? '';
                $context['product_price'] = $variant->sellingPrice ?? 0;
            }
        }

        // Merge any additional context from event
        foreach ($eventData as $key => $value) {
            if (!isset($context[$key]) && !in_array($key, ['clientId', 'orderId', 'storeId', 'productId', 'variantId'])) {
                $context[$key] = $value;
            }
        }

        return $context;
    }

    /**
     * Generate a random password for login access.
     *
     * @param int $length Password length (default 8)
     * @return string
     */
    protected function generateRandomPassword($length = 8)
    {
        // Use a mix of characters that are easy to read and type
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }
}
