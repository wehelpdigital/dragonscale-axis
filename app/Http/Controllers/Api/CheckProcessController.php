<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessToken;
use App\Models\Task;
use App\Models\NotificationEmail;
use App\Models\NotificationReceiver;
use App\Models\NotificationHistory;
use App\Models\ThresholdTask;
use App\Models\HistoricalPrice;
use App\Models\HistoricalLadder;
use App\Models\DifferenceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CheckProcessController extends Controller
{
    /**
     * Handle the check-process API request
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkProcess(Request $request): JsonResponse
    {
        // Get token from request
        $token = $request->get('token');

        // Check if token is provided
        if (!$token) {
            return response()->json([
                'error' => 'Token is required',
                'message' => 'Please provide a valid token parameter'
            ], 401);
        }

        // Verify token exists in database
        $accessToken = AccessToken::where('token', $token)->first();

        if (!$accessToken) {
            return response()->json([
                'error' => 'Invalid token',
                'message' => 'The provided token is not valid'
            ], 401);
        }

        // Get tasks with current status
        $currentTasks = Task::current()->get();

        $processedTasks = [];

        // Loop through each task and process it
        foreach ($currentTasks as $task) {
            $result = $this->processTask($task);
            $processedTasks[] = $result;
        }

        // Save crypto price data after all processes are completed
        $this->saveCryptoPrice($processedTasks);

        // Save crypto ladder data after all processes are completed
        $this->saveCryptoLadder($processedTasks);

        // Save difference history data after all processes are completed
        $this->saveDifferenceHistory($processedTasks);

        // Token is valid, return processed tasks directly
        return response()->json($processedTasks, 200);
    }

    /**
     * Process individual task
     *
     * @param Task $task
     * @return array
     */
    private function processTask(Task $task): array
    {
        // Check if taskCoin is btc
        if ($task->taskCoin === 'btc') {
            return $this->processBtc($task);
        }

        // For now, just print the currentCoinValue
        $currentCoinValue = $task->currentCoinValue;

        // You can add logging here if needed
        Log::info("Processing task ID: {$task->id}, Current Coin Value: {$currentCoinValue}");

        return [
            'task_id' => $task->id,
            'current_coin_value' => $currentCoinValue,
            'status' => 'processed'
        ];
    }

    /**
     * Process BTC specific tasks
     *
     * @param Task $task
     * @return array
     */
    private function processBtc(Task $task): array
    {
        // Check taskType and route accordingly
        if ($task->taskType === 'to buy') {
            return $this->toBuyProcess($task);
        } elseif ($task->taskType === 'to sell') {
            return $this->toSellProcess($task);
        }

        // Default BTC processing if taskType doesn't match
        $currentCoinValue = $task->currentCoinValue;
        Log::info("Processing BTC task ID: {$task->id}, Current Coin Value: {$currentCoinValue}");

        return [
            'task_id' => $task->id,
            'current_coin_value' => $currentCoinValue,
            'coin_type' => 'btc',
            'task_type' => $task->taskType,
            'status' => 'processed'
        ];
    }

        /**
     * Process BTC buy tasks
     *
     * @param Task $task
     * @return array
     */
    private function toBuyProcess(Task $task): array
    {
        // Get the required values from task table
        $toBuyCurrentCashValue = $task->toBuyCurrentCashValue;
        $toBuyStartingCoinValue = $task->toBuyStartingCoinValue;
        $toBuyMinThreshold = $task->toBuyMinThreshold;
        $toBuyIntervalThreshold = $task->toBuyIntervalThreshold;

        Log::info("Processing BTC BUY task ID: {$task->id}, Current Cash Value: {$toBuyCurrentCashValue}, Starting Coin Value: {$toBuyStartingCoinValue}");

        // Get BTC price from CoinGecko API
        $btcPriceData = $this->getBtcPriceFromCoinGecko();

        if (isset($btcPriceData['error'])) {
            return [
                'task_data' => [
                    'id' => $task->id,
                    'usersId' => $task->usersId,
                    'currentCoinValue' => $task->currentCoinValue,
                    'taskCoin' => $task->taskCoin,
                    'taskType' => $task->taskType,
                    'toBuyCurrentCashValue' => $toBuyCurrentCashValue,
                    'toBuyStartingCoinValue' => $toBuyStartingCoinValue,
                    'toBuyMinThreshold' => $toBuyMinThreshold,
                    'toBuyIntervalThreshold' => $toBuyIntervalThreshold,
                    'status' => $task->status
                ],
                'error' => $btcPriceData['error']
            ];
        }

        $btcToPhpRate = $btcPriceData['btc_to_php_rate'];

        // Calculate how many BTC can be purchased
        $toBuyCoinAmount = $toBuyCurrentCashValue / $btcToPhpRate;

        // Calculate CoinsPH charge in BTC
        $coinsChargeInBtc = $this->getCoinsChargeForBuy($toBuyCurrentCashValue, $toBuyCoinAmount);

        // Calculate final BTC amount after charge
        $finalBtcAmount = $toBuyCoinAmount - $coinsChargeInBtc;

        // Calculate difference from starting coin value
        $btcDifference = $finalBtcAmount - $toBuyStartingCoinValue;

        // Convert difference to PHP value
        $differenceInPhp = $btcDifference * $btcToPhpRate;

        // Check if difference is greater than minimum threshold
        if ($differenceInPhp > $toBuyMinThreshold) {
            $thresholdResult = $this->checkToBuyThresholdTask($task, $differenceInPhp, $finalBtcAmount);
            return [
                'task_id' => $task->id,
                'buy_calculation_data' => [
                    'btc_to_php_rate' => $btcToPhpRate,
                    'to_buy_coin_amount' => $toBuyCoinAmount,
                    'coins_charge_in_btc' => $coinsChargeInBtc,
                    'final_btc_amount' => $finalBtcAmount,
                    'btc_difference' => $btcDifference,
                    'difference_in_php' => $differenceInPhp
                ],
                'threshold_status' => $thresholdResult['status'],
                'threshold_quotient' => $thresholdResult['threshold_quotient'],
                'notification_status' => $thresholdResult['notification_status'] ?? null,
                'notification_email_data' => $thresholdResult['notification_email_data'] ?? null,
                'notification_receiver_data' => $thresholdResult['notification_receiver_data'] ?? null
            ];
        }

        return [
            'task_id' => $task->id,
            'buy_calculation_data' => [
                'btc_to_php_rate' => $btcToPhpRate,
                'to_buy_coin_amount' => $toBuyCoinAmount,
                'coins_charge_in_btc' => $coinsChargeInBtc,
                'final_btc_amount' => $finalBtcAmount,
                'btc_difference' => $btcDifference,
                'difference_in_php' => $differenceInPhp
            ]
        ];
    }

    /**
     * Get BTC price from CoinGecko API
     *
     * @return array
     */
    private function getBtcPriceFromCoinGecko(): array
    {
        $apiKey = 'CG-k3ZFMjdtUQf6a1XNNAA4ajc2';
        $url = "https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=php&x_cg_demo_api_key={$apiKey}";

        try {
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            if ($data && isset($data['bitcoin']['php'])) {
                return [
                    'btc_to_php_rate' => $data['bitcoin']['php'],
                    'coingecko_data' => $data
                ];
            } else {
                Log::error("Failed to get BTC price from CoinGecko");
                return [
                    'error' => 'Failed to get BTC price'
                ];
            }
        } catch (\Exception $e) {
            Log::error("CoinGecko API error: " . $e->getMessage());
            return [
                'error' => 'API request failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate CoinsPH charge for buying BTC
     *
     * @param float $cashValue
     * @param float $btcAmount
     * @return float
     */
    private function getCoinsChargeForBuy($cashValue, $btcAmount): float
    {
        // CoinsPH typically charges around 0.5% for buying
        $chargePercentage = 0.005; // 0.5% charge

        $chargeInPhp = $cashValue * $chargePercentage;
        $chargeInBtc = $chargeInPhp / ($cashValue / $btcAmount); // Convert PHP charge to BTC

        Log::info("CoinsPH buy charge calculation: Cash Value: {$cashValue}, BTC Amount: {$btcAmount}, Charge in BTC: {$chargeInBtc}");

        return $chargeInBtc;
    }

    /**
     * Check buy threshold task
     *
     * @param Task $task
     * @param float $differenceInPhp
     * @param float $finalBtcAmount
     * @return array
     */
    private function checkToBuyThresholdTask(Task $task, $differenceInPhp, $finalBtcAmount): array
    {
        Log::info("Checking buy threshold for task ID: {$task->id}, Difference in PHP: {$differenceInPhp}, Final BTC Amount: {$finalBtcAmount}");

        // Calculate threshold quotient
        $thresholdQuotient = intval($differenceInPhp / $task->toBuyIntervalThreshold);
        $currentDate = Carbon::now('Asia/Manila')->format('Y-m-d');

        // Check if threshold task already exists for today
        $existingThresholdTask = ThresholdTask::where([
            'usersId' => $task->usersId,
            'taskId' => $task->id,
            'thresholdQuotient' => $thresholdQuotient,
            'date' => $currentDate
        ])->first();

        if (!$existingThresholdTask) {
            // Create new threshold task record
            ThresholdTask::create([
                'usersId' => $task->usersId,
                'taskId' => $task->id,
                'thresholdQuotient' => $thresholdQuotient,
                'date' => $currentDate
            ]);

            // Send notification
            $notificationResult = $this->sendNotificationToBuy($task, $differenceInPhp, $finalBtcAmount);

            return [
                'status' => 'New threshold created and notification sent',
                'threshold_quotient' => $thresholdQuotient,
                'notification_status' => $notificationResult['status'],
                'notification_email_data' => $notificationResult['notification_email_data'],
                'notification_receiver_data' => $notificationResult['notification_receiver_data']
            ];
        } else {
            // Threshold already exists for today
            return [
                'status' => 'You are still in the existing threshold',
                'threshold_quotient' => $thresholdQuotient
            ];
        }
    }

    /**
     * Send notification for buy threshold
     *
     * @param Task $task
     * @param float $differenceInPhp
     * @param float $finalBtcAmount
     * @return array
     */
    private function sendNotificationToBuy(Task $task, $differenceInPhp, $finalBtcAmount): array
    {
        Log::info("Sending buy notification for task ID: {$task->id}, Difference in PHP: {$differenceInPhp}, Final BTC Amount: {$finalBtcAmount}");
        Log::info("Task usersId: {$task->usersId}");

        try {
            // Get SMTP credentials from notification_email table
            $notificationEmail = NotificationEmail::where('usersId', $task->usersId)->first();

            // Debug: Log the query and result
            Log::info("Looking for notification email with usersId: {$task->usersId}");

            // Check if there are any records in the table at all
            $allNotificationEmails = NotificationEmail::all();
            Log::info("Total notification email records: " . $allNotificationEmails->count());
            if ($allNotificationEmails->count() > 0) {
                Log::info("Available usersId values: " . $allNotificationEmails->pluck('usersId')->implode(', '));
            }

            Log::info("Notification email found: " . ($notificationEmail ? 'YES' : 'NO'));
            if ($notificationEmail) {
                Log::info("Email config: " . json_encode($notificationEmail->toArray()));
            }

            if (!$notificationEmail) {
                Log::error("No notification email configuration found for user ID: {$task->usersId}");
                return [
                    'status' => 'No email configuration found',
                    'notification_email_data' => null,
                    'notification_receiver_data' => null
                ];
            }

            // Get email recipient from notification_receiver table
            $notificationReceiver = NotificationReceiver::where('usersId', $task->usersId)->first();

            if (!$notificationReceiver) {
                Log::error("No notification receiver found for user ID: {$task->usersId}");
                return [
                    'status' => 'No notification receiver found',
                    'notification_email_data' => [
                        'usersid' => $notificationEmail->usersid,
                        'email' => $notificationEmail->email,
                        'smtp_host' => $notificationEmail->smtp_host,
                        'smtp_port' => $notificationEmail->smtp_port
                    ],
                    'notification_receiver_data' => null
                ];
            }

            // Configure SMTP settings
            $config = [
                'transport' => 'smtp',
                'host' => $notificationEmail->smtp_host,
                'port' => $notificationEmail->smtp_port,
                'username' => $notificationEmail->email,
                'password' => $notificationEmail->password,
                'encryption' => 'tls',
                'timeout' => null,
                'local_domain' => env('MAIL_EHLO_DOMAIN'),
            ];

            // Set the mail configuration
            config(['mail.mailers.smtp' => $config]);
            config(['mail.from.address' => $notificationEmail->email]);
            config(['mail.from.name' => 'Crypto Alert System']);

            // Email subject
            $subject = "Buy Crypto To Earn - " . number_format($differenceInPhp, 2);

            // Email body
            $message = "The earning you will get if you buy now is " . number_format($differenceInPhp, 2) . " PHP.\n\n";
            $message .= "AI Analysis: <a href='#'>Click here for AI analysis</a>";

            // Send email to the recipient from notification_receiver table
            Mail::raw($message, function($mail) use ($notificationReceiver, $subject) {
                $mail->to($notificationReceiver->emailRecipient)
                     ->subject($subject)
                     ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info("Buy email sent successfully to: {$notificationReceiver->emailRecipient}");

            // Save notification history after email is sent successfully
            $this->saveNotificationHistory($task->id, $task->usersId, $finalBtcAmount, $differenceInPhp);

            return [
                'status' => 'Buy email notification sent successfully',
                'notification_email_data' => [
                    'usersId' => $notificationEmail->usersId,
                    'email' => $notificationEmail->email,
                    'smtp_host' => $notificationEmail->smtp_host,
                    'smtp_port' => $notificationEmail->smtp_port
                ],
                'notification_receiver_data' => [
                    'usersId' => $notificationReceiver->usersId,
                    'emailRecipient' => $notificationReceiver->emailRecipient
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Failed to send buy email notification: " . $e->getMessage());
            return [
                'status' => 'Failed to send buy email notification: ' . $e->getMessage(),
                'notification_email_data' => null,
                'notification_receiver_data' => null
            ];
        }
    }

    /**
     * Process BTC sell tasks
     *
     * @param Task $task
     * @return array
     */
    private function toSellProcess(Task $task): array
    {
        $currentCoinValue = $task->currentCoinValue;
        Log::info("Processing BTC SELL task ID: {$task->id}, Current Coin Value: {$currentCoinValue}");

        // Get the potential sell value in PHP
        $sellValueData = $this->checkToSellValue($currentCoinValue);
        $finalAmount = $sellValueData['final_amount_after_charge'];

                // Calculate difference from starting PHP value
        $difference = $finalAmount - $task->startingPhpValue;

        // Check if difference is greater than minimum threshold
        if ($difference > $task->minThreshold) {
            // Calculate threshold quotient
            $thresholdQuotient = intval($difference / $task->intervalThreshold);
            $currentDate = Carbon::now('Asia/Manila')->format('Y-m-d');

            // Check if threshold task already exists for today
            $existingThresholdTask = ThresholdTask::where([
                'usersId' => $task->usersId,
                'taskId' => $task->id,
                'thresholdQuotient' => $thresholdQuotient,
                'date' => $currentDate
            ])->first();

            if (!$existingThresholdTask) {
                // Create new threshold task record
                ThresholdTask::create([
                    'usersId' => $task->usersId,
                    'taskId' => $task->id,
                    'thresholdQuotient' => $thresholdQuotient,
                    'date' => $currentDate
                ]);

                // Send notification
                $notificationResult = $this->sendNotificationToSell($task, $difference, $finalAmount);

                return [
                    'task_data' => [
                        'id' => $task->id,
                        'usersId' => $task->usersId,
                        'currentCoinValue' => $task->currentCoinValue,
                        'taskCoin' => $task->taskCoin,
                        'taskType' => $task->taskType,
                        'startingPhpValue' => $task->startingPhpValue,
                        'minThreshold' => $task->minThreshold,
                        'intervalThreshold' => $task->intervalThreshold,
                        'status' => $task->status
                    ],
                    'final_amount_after_charge' => $finalAmount,
                    'difference' => $difference,
                    'threshold_quotient' => $thresholdQuotient,
                    'notification_status' => $notificationResult['status'],
                    'notification_email_data' => $notificationResult['notification_email_data'],
                    'notification_receiver_data' => $notificationResult['notification_receiver_data']
                ];
            } else {
                // Threshold already exists for today
                return [
                    'task_data' => [
                        'id' => $task->id,
                        'usersId' => $task->usersId,
                        'currentCoinValue' => $task->currentCoinValue,
                        'taskCoin' => $task->taskCoin,
                        'taskType' => $task->taskType,
                        'startingPhpValue' => $task->startingPhpValue,
                        'minThreshold' => $task->minThreshold,
                        'intervalThreshold' => $task->intervalThreshold,
                        'status' => $task->status
                    ],
                    'final_amount_after_charge' => $finalAmount,
                    'difference' => $difference,
                    'threshold_quotient' => $thresholdQuotient,
                    'threshold_status' => 'You are still in the existing threshold'
                ];
            }
        }

        return [
            'task_data' => [
                'id' => $task->id,
                'usersId' => $task->usersId,
                'currentCoinValue' => $task->currentCoinValue,
                'taskCoin' => $task->taskCoin,
                'taskType' => $task->taskType,
                'startingPhpValue' => $task->startingPhpValue,
                'minThreshold' => $task->minThreshold,
                'status' => $task->status
            ],
            'final_amount_after_charge' => $finalAmount,
            'difference' => $difference
        ];
    }

        /**
     * Send notification when threshold is met
     *
     * @param Task $task
     * @param float $difference
     * @param float $finalAmount
     * @return array
     */
    private function sendNotificationToSell(Task $task, $difference, $finalAmount): array
    {
        Log::info("Sending notification for task ID: {$task->id}, Difference: {$difference}, Final Amount: {$finalAmount}");

        try {
            // Get SMTP credentials from notification_email table
            $notificationEmail = NotificationEmail::where('usersId', $task->usersId)->first();

            if (!$notificationEmail) {
                Log::error("No notification email configuration found for user ID: {$task->usersId}");
                return [
                    'status' => 'No email configuration found',
                    'notification_email_data' => null,
                    'notification_receiver_data' => null
                ];
            }

            // Get email recipient from notification_receiver table
            $notificationReceiver = NotificationReceiver::where('usersId', $task->usersId)->first();

            if (!$notificationReceiver) {
                Log::error("No notification receiver found for user ID: {$task->usersId}");
                return [
                    'status' => 'No notification receiver found',
                    'notification_email_data' => [
                        'usersid' => $notificationEmail->usersid,
                        'email' => $notificationEmail->email,
                        'smtp_host' => $notificationEmail->smtp_host,
                        'smtp_port' => $notificationEmail->smtp_port
                    ],
                    'notification_receiver_data' => null
                ];
            }

            // Configure SMTP settings
            $config = [
                'transport' => 'smtp',
                'host' => $notificationEmail->smtp_host,
                'port' => $notificationEmail->smtp_port,
                'username' => $notificationEmail->email,
                'password' => $notificationEmail->password,
                'encryption' => 'tls',
                'timeout' => null,
                'local_domain' => env('MAIL_EHLO_DOMAIN'),
            ];

            // Set the mail configuration
            config(['mail.mailers.smtp' => $config]);
            config(['mail.from.address' => $notificationEmail->email]);
            config(['mail.from.name' => 'Crypto Alert System']);

            // Email subject
            $subject = "Sell Your Crypto To Earn - " . number_format($difference, 2);

            // Email body
            $message = "The earning you will get if you sell now is " . number_format($difference, 2) . " PHP.\n\n";
            $message .= "AI Analysis: <a href='#'>Click here for AI analysis</a>";

            // Send email to the recipient from notification_receiver table
            Mail::raw($message, function($mail) use ($notificationReceiver, $subject) {
                $mail->to($notificationReceiver->emailRecipient)
                     ->subject($subject)
                     ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info("Email sent successfully to: {$notificationReceiver->emailRecipient}");

            // Save notification history after email is sent successfully
            $this->saveNotificationHistory($task->id, $task->usersId, $finalAmount, $difference);

            return [
                'status' => 'Email notification sent successfully',
                'notification_email_data' => [
                    'usersId' => $notificationEmail->usersId,
                    'email' => $notificationEmail->email,
                    'smtp_host' => $notificationEmail->smtp_host,
                    'smtp_port' => $notificationEmail->smtp_port
                ],
                'notification_receiver_data' => [
                    'usersId' => $notificationReceiver->usersId,
                    'emailRecipient' => $notificationReceiver->emailRecipient
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Failed to send email notification: " . $e->getMessage());
            return [
                'status' => 'Failed to send email notification: ' . $e->getMessage(),
                'notification_email_data' => null,
                'notification_receiver_data' => null
            ];
        }
    }

    /**
     * Check the potential sell value in Philippine Peso
     *
     * @param float $currentCoinValue
     * @return array
     */
    private function checkToSellValue($currentCoinValue): array
    {
        $apiKey = 'CG-k3ZFMjdtUQf6a1XNNAA4ajc2';
        $url = "https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=php&x_cg_demo_api_key={$apiKey}";

        try {
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            if ($data && isset($data['bitcoin']['php'])) {
                $btcToPhpRate = $data['bitcoin']['php'];
                $potentialValue = $currentCoinValue * $btcToPhpRate;

                // Calculate CoinsPH charge
                $coinsCharge = $this->getCoinsCharge($potentialValue, $currentCoinValue);

                // Calculate final amount after charge
                $finalAmount = $potentialValue - $coinsCharge;

                return [
                    'btc_to_php_rate' => $btcToPhpRate,
                    'potential_value_php' => $potentialValue,
                    'coins_charge' => $coinsCharge,
                    'final_amount_after_charge' => $finalAmount,
                    'coingecko_data' => $data
                ];
            } else {
                Log::error("Failed to get BTC price from CoinGecko");
                return [
                    'error' => 'Failed to get BTC price',
                    'coingecko_data' => $data
                ];
            }
        } catch (\Exception $e) {
            Log::error("CoinGecko API error: " . $e->getMessage());
            return [
                'error' => 'API request failed',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate CoinsPH charge for selling BTC
     *
     * @param float $potentialValue
     * @param float $currentCoinValue
     * @return float
     */
    private function getCoinsCharge($potentialValue, $currentCoinValue): float
    {
        // Based on actual CoinsPH rates
        // For 0.0184134 BTC at ₱6,490,512.7 = ₱119,512.4
        // The charge appears to be much lower than 1.5%
        // Using a more conservative rate of 0.5%
        $chargePercentage = 0.005; // 0.5% charge

        $charge = $potentialValue * $chargePercentage;

        Log::info("CoinsPH charge calculation: Value: {$potentialValue}, Charge: {$charge}");

        return $charge;
    }

    /**
     * Save notification history after email is sent
     *
     * @param int $taskId
     * @param int $usersId
     * @param float $finalAmount
     * @param float $difference
     * @return void
     */
    private function saveNotificationHistory($taskId, $usersId, $finalAmount, $difference): void
    {
        try {
            $notificationHistory = NotificationHistory::create([
                'taskId' => $taskId,
                'usersId' => $usersId,
                'finalAmount' => (int) round($finalAmount),
                'difference' => (int) round($difference),
            ]);

            Log::info("Notification history saved for task ID: {$taskId}, usersId: {$usersId}, record ID: {$notificationHistory->id}");
        } catch (\Exception $e) {
            Log::error("Failed to save notification history: " . $e->getMessage());
        }
    }

    /**
     * Save crypto price data after all processes are completed
     *
     * @param array $processedTasks
     * @return void
     */
    private function saveCryptoPrice(array $processedTasks): void
    {
        try {
            Log::info("Starting saveCryptoPrice function");

            // Trigger saveBTCPrice function
            $this->saveBTCPrice($processedTasks);

            Log::info("saveCryptoPrice function completed successfully");
        } catch (\Exception $e) {
            Log::error("Error in saveCryptoPrice: " . $e->getMessage());
        }
    }

        /**
     * Save BTC price to historical_price table
     *
     * @param array $processedTasks
     * @return void
     */
    private function saveBTCPrice(array $processedTasks): void
    {
        try {
            Log::info("Starting saveBTCPrice function");

            // Get BTC price from CoinGecko API (only once)
            $btcPriceData = $this->getBtcPriceFromCoinGecko();

            if (isset($btcPriceData['error'])) {
                Log::error("Failed to get BTC price for historical save: " . $btcPriceData['error']);
                return;
            }

            $btcToPhpRate = $btcPriceData['btc_to_php_rate'];

            // Save to historical_price table
            $historicalPrice = HistoricalPrice::create([
                'coinType' => 'btc',
                'valueInPhp' => $btcToPhpRate,
            ]);

            Log::info("BTC price saved to historical_price table: ID {$historicalPrice->id}, Value: {$btcToPhpRate} PHP");

        } catch (\Exception $e) {
            Log::error("Error in saveBTCPrice: " . $e->getMessage());
        }
    }

    /**
     * Save crypto ladder data after all processes are completed
     *
     * @param array $processedTasks
     * @return void
     */
    private function saveCryptoLadder(array $processedTasks): void
    {
        try {
            Log::info("Starting saveCryptoLadder function");

            // Trigger saveBTCLadder function
            $this->saveBTCLadder($processedTasks);

            Log::info("saveCryptoLadder function completed successfully");
        } catch (\Exception $e) {
            Log::error("Error in saveCryptoLadder: " . $e->getMessage());
        }
    }

    /**
     * Save BTC ladder data to historical_ladder table
     *
     * @param array $processedTasks
     * @return void
     */
    private function saveBTCLadder(array $processedTasks): void
    {
        try {
            Log::info("Starting saveBTCLadder function");

            // Get current BTC price from CoinGecko API
            $btcPriceData = $this->getBtcPriceFromCoinGecko();

            if (isset($btcPriceData['error'])) {
                Log::error("Failed to get BTC price for ladder save: " . $btcPriceData['error']);
                return;
            }

            $currentPrice = $btcPriceData['btc_to_php_rate'];
            $currentTimestamp = Carbon::now('Asia/Manila');

            // Get the past 72 most recent intervals from historical_price table
            $historicalPrices = HistoricalPrice::where('coinType', 'btc')
                ->orderBy('created_at', 'desc')
                ->limit(72)
                ->get()
                ->reverse(); // Reverse to get chronological order

            if ($historicalPrices->isEmpty()) {
                Log::warning("No historical price data found for BTC ladder calculation");
                return;
            }

            // Calculate values array (percentage changes)
            $values = [];
            foreach ($historicalPrices as $price) {
                $pctChange = ($currentPrice / $price->valueInPhp) - 1;
                $values[] = round($pctChange, 5);
            }

            // Calculate diffs for each interval
            $diffs = [];
            $intervalMinutes = 5; // 5-minute intervals

            foreach ($historicalPrices as $index => $price) {
                $intervalKey = ($index + 1) * $intervalMinutes;
                $absDiff = $currentPrice - $price->valueInPhp;
                $pctDiff = ($currentPrice / $price->valueInPhp) - 1;

                $diffs[$intervalKey] = [
                    'ts' => $price->created_at->setTimezone('Asia/Manila')->toISOString(),
                    'abs' => round($absDiff, 2),
                    'pct' => round($pctDiff, 5)
                ];
            }

            // Create the ladder data structure
            $ladderData = [
                'id' => 'btc-' . $currentTimestamp->toISOString(),
                'values' => $values,
                'metadata' => [
                    'asset' => 'BTC',
                    'interval_min' => 5,
                    'ts' => $currentTimestamp->toISOString(),
                    'close' => round($currentPrice, 2),
                    'diffs' => $diffs,
                    'future_ret_5m' => null,
                    'future_ret_15m' => null,
                    'label_15m' => null
                ]
            ];

            // Save to historical_ladder table
            $historicalLadder = HistoricalLadder::create([
                'data' => $ladderData
            ]);

            Log::info("BTC ladder data saved to historical_ladder table: ID {$historicalLadder->id}, Records: " . count($values));

        } catch (\Exception $e) {
            Log::error("Error in saveBTCLadder: " . $e->getMessage());
        }
    }

    /**
     * Save difference history data after all processes are completed
     *
     * @param array $processedTasks
     * @return void
     */
    private function saveDifferenceHistory(array $processedTasks): void
    {
        try {
            Log::info("Starting saveDifferenceHistory function");
            Log::info("Processed tasks count: " . count($processedTasks));

            foreach ($processedTasks as $index => $taskResult) {
                Log::info("Processing task result {$index}: " . json_encode($taskResult));

                // Get the task data
                $taskData = null;
                $task = null;

                if (isset($taskResult['task_data'])) {
                    $taskData = $taskResult['task_data'];
                    Log::info("Found task_data in result");
                } elseif (isset($taskResult['buy_calculation_data'])) {
                    // For buy tasks, we need to get the task from the database
                    $task = Task::find($taskResult['task_id'] ?? null);
                    if ($task) {
                        $taskData = [
                            'id' => $task->id,
                            'usersId' => $task->usersId,
                            'taskType' => $task->taskType,
                            'currentCoinValue' => $task->currentCoinValue,
                            'startingPhpValue' => $task->startingPhpValue,
                            'toBuyCurrentCashValue' => $task->toBuyCurrentCashValue,
                            'toBuyStartingCoinValue' => $task->toBuyStartingCoinValue,
                        ];
                        Log::info("Found buy_calculation_data, retrieved task from database");
                    }
                } elseif (isset($taskResult['task_id'])) {
                    // For buy tasks without task_data, get from database
                    $task = Task::find($taskResult['task_id']);
                    if ($task) {
                        $taskData = [
                            'id' => $task->id,
                            'usersId' => $task->usersId,
                            'taskType' => $task->taskType,
                            'currentCoinValue' => $task->currentCoinValue,
                            'startingPhpValue' => $task->startingPhpValue,
                            'toBuyCurrentCashValue' => $task->toBuyCurrentCashValue,
                            'toBuyStartingCoinValue' => $task->toBuyStartingCoinValue,
                        ];
                        Log::info("Found task_id, retrieved task from database");
                    }
                }

                if (!$taskData) {
                    Log::warning("No task data found for difference history save in result {$index}");
                    continue;
                }

                Log::info("Task data found: " . json_encode($taskData));

                // Calculate cash difference based on task type
                $cashDifference = 0;
                $toSellCurrentCoinValue = 0;
                $toSellStartingPhpValue = 0;
                $toBuyCurrentCashValue = 0;
                $toBuyStartingCoinValue = 0;

                if ($taskData['taskType'] === 'to sell') {
                    // For sell tasks, calculate difference from final amount
                    if (isset($taskResult['final_amount_after_charge']) && isset($taskData['startingPhpValue'])) {
                        $cashDifference = $taskResult['final_amount_after_charge'] - $taskData['startingPhpValue'];
                        $toSellCurrentCoinValue = $taskData['currentCoinValue'];
                        $toSellStartingPhpValue = $taskData['startingPhpValue'];
                        Log::info("Sell task - cashDifference: {$cashDifference}, final_amount: {$taskResult['final_amount_after_charge']}, startingPhpValue: {$taskData['startingPhpValue']}");
                    }
                } elseif ($taskData['taskType'] === 'to buy') {
                    // For buy tasks, calculate difference from buy calculation data
                    if (isset($taskResult['buy_calculation_data']['difference_in_php'])) {
                        $cashDifference = $taskResult['buy_calculation_data']['difference_in_php'];
                        $toBuyCurrentCashValue = $taskData['toBuyCurrentCashValue'];
                        $toBuyStartingCoinValue = $taskData['toBuyStartingCoinValue'];
                        Log::info("Buy task - cashDifference: {$cashDifference}");
                    }
                }

                // Save to difference_history table
                $differenceHistory = DifferenceHistory::create([
                    'usersId' => $taskData['usersId'],
                    'taskType' => $taskData['taskType'],
                    'toSellCurrentCoinValue' => $toSellCurrentCoinValue,
                    'toSellStartingPhpValue' => $toSellStartingPhpValue,
                    'toBuyCurrentCashValue' => $toBuyCurrentCashValue,
                    'toBuyStartingCoinValue' => $toBuyStartingCoinValue,
                    'cashDifference' => $cashDifference,
                ]);

                Log::info("Difference history saved for task ID: {$taskData['id']}, usersId: {$taskData['usersId']}, record ID: {$differenceHistory->id}, cashDifference: {$cashDifference}");
            }

            Log::info("saveDifferenceHistory function completed successfully");
        } catch (\Exception $e) {
            Log::error("Error in saveDifferenceHistory: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
