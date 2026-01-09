# Logic Architect - DS AXIS Logic & Data Flow Map

> **Purpose**: This skill provides a comprehensive map of all logic flows, data flows, processing workflows, and external integrations in the DS AXIS (Dragon Scale Axis) application. It synthesizes information from `codebase-architect.md` (system structure) and `database-architect.md` (data schema) to document how data moves through the system and how business logic is executed.
>
> **System Context**: DS AXIS is the centralized admin dashboard for Dragon Scale Web Company. As the parent system managing multiple sub-ventures, the logic flows documented here represent administrative operations across all business modules.

---

## 1. System Architecture & Skill Harmony

### 1.1 DS AXIS Purpose

DS AXIS serves as the **centralized admin backend** for Dragon Scale Web Company, managing:
- **Crypto Investment** (Dragon Scale Crypto)
- **E-commerce** (Dragon Scale Store(s))
- **Courses & Memberships** (Ani-Senso Academy)
- **CRM & Client Management** (Cross-Venture)
- **Access Control & Triggers** (Cross-Venture)
- **Affiliates** (Cross-Venture, expanding)
- **Future Ventures** (Designed for growth)

### 1.2 Skill System Architecture

This skill works in conjunction with companion skills:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         ARCHITECT SKILL SYSTEM                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────┐                     ┌─────────────────────┐   │
│  │  CODEBASE-ARCHITECT │◄───────────────────►│ DATABASE-ARCHITECT  │   │
│  │  (System Structure) │                     │   (Data Schema)     │   │
│  │                     │                     │                     │   │
│  │  • Directory layout │                     │  • Table schemas    │   │
│  │  • MVC patterns     │                     │  • Relationships    │   │
│  │  • View templates   │                     │  • Data types       │   │
│  │  • JS patterns      │                     │  • Indexes          │   │
│  │  • Route conventions│                     │  • Soft deletes     │   │
│  └──────────┬──────────┘                     └──────────┬──────────┘   │
│             │                                           │               │
│             └────────────────┬──────────────────────────┘               │
│                              │                                          │
│                              ▼                                          │
│                 ┌─────────────────────────┐                            │
│                 │    LOGIC-ARCHITECT      │                            │
│                 │   (Data & Logic Flow)   │                            │
│                 │                         │                            │
│                 │  • Business logic       │                            │
│                 │  • Data transformations │                            │
│                 │  • External APIs        │                            │
│                 │  • Processing workflows │                            │
│                 │  • Authentication flows │                            │
│                 │  • Notification systems │                            │
│                 └─────────────────────────┘                            │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Authentication & Authorization Flows

### 2.1 Web Authentication Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        WEB AUTHENTICATION FLOW                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  User                                                                   │
│    │                                                                    │
│    ▼                                                                    │
│  [Login Form] ──► [POST /login]                                        │
│                         │                                               │
│                         ▼                                               │
│              ┌─────────────────────┐                                   │
│              │   LoginController   │                                   │
│              │   attemptLogin()    │                                   │
│              └──────────┬──────────┘                                   │
│                         │                                               │
│                         ▼                                               │
│              ┌─────────────────────────────────────────┐               │
│              │  Query: SELECT * FROM users             │               │
│              │  WHERE email = ?                        │               │
│              │  AND (delete_status = 'active'          │               │
│              │       OR delete_status IS NULL)         │               │
│              └──────────┬──────────────────────────────┘               │
│                         │                                               │
│                         ▼                                               │
│              ┌─────────────────────┐                                   │
│              │  Hash::check()      │                                   │
│              │  Verify password    │                                   │
│              └──────────┬──────────┘                                   │
│                         │                                               │
│           ┌─────────────┴─────────────┐                                │
│           ▼                           ▼                                 │
│     [Success]                    [Failure]                             │
│           │                           │                                 │
│           ▼                           ▼                                 │
│  session()->regenerate()     ValidationException                       │
│           │                   "auth.failed"                            │
│           ▼                                                            │
│  redirect('/welcome')                                                  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

**Key Files**:
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Middleware/Authenticate.php`

**Authorization Pattern**: All protected routes use `->middleware('auth')` which redirects to `/login` if unauthenticated.

### 2.2 API Token Authentication Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      API TOKEN AUTHENTICATION                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  External Request                                                       │
│    │                                                                    │
│    ▼                                                                    │
│  [GET /api/check-process?token=xxx]                                    │
│                │                                                        │
│                ▼                                                        │
│  ┌──────────────────────────────┐                                      │
│  │  CheckProcessController      │                                      │
│  │  checkProcess()              │                                      │
│  └──────────────┬───────────────┘                                      │
│                 │                                                       │
│                 ▼                                                       │
│  ┌──────────────────────────────────────┐                              │
│  │  $token = $request->get('token')     │                              │
│  └──────────────┬───────────────────────┘                              │
│                 │                                                       │
│                 ▼                                                       │
│  ┌──────────────────────────────────────┐                              │
│  │  AccessToken::where('token', $token) │                              │
│  │  ->first()                           │                              │
│  └──────────────┬───────────────────────┘                              │
│                 │                                                       │
│      ┌──────────┴──────────┐                                           │
│      ▼                     ▼                                            │
│  [Token Valid]        [Token Invalid]                                  │
│      │                     │                                            │
│      ▼                     ▼                                            │
│  Continue                401 JSON Response                             │
│  Processing              "Invalid token"                               │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

**Table**: `access-token` (legacy hyphenated name)

---

## 3. Crypto Module Logic Flows

### 3.1 Main API Processing Flow (CheckProcessController)

This is the **core business logic** that runs periodically to check crypto prices and trigger notifications.

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    CRYPTO CHECK PROCESS FLOW                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  [GET /api/check-process?token=xxx]                                    │
│                │                                                        │
│                ▼                                                        │
│  ┌─────────────────────────────┐                                       │
│  │    Validate Token           │                                       │
│  │    (access-token table)     │                                       │
│  └──────────────┬──────────────┘                                       │
│                 │                                                       │
│                 ▼                                                       │
│  ┌─────────────────────────────┐                                       │
│  │  Task::current()->get()     │                                       │
│  │  Get all active tasks       │                                       │
│  └──────────────┬──────────────┘                                       │
│                 │                                                       │
│                 ▼                                                       │
│  ┌─────────────────────────────┐                                       │
│  │  foreach ($currentTasks)    │◄────────────────┐                     │
│  │     processTask($task)      │                 │                     │
│  └──────────────┬──────────────┘                 │                     │
│                 │                                │                     │
│                 ▼                                │                     │
│     ┌───────────────────────┐                   │                     │
│     │ Is taskCoin == 'btc'? │                   │                     │
│     └───────────┬───────────┘                   │                     │
│           Yes   │   No                          │                     │
│          ┌──────┴──────┐                        │                     │
│          ▼             ▼                        │                     │
│    processBtc()   Log & Return                  │                     │
│          │                                      │                     │
│          ▼                                      │                     │
│  ┌───────────────────────┐                     │                     │
│  │ Check taskType        │                     │                     │
│  └───────────┬───────────┘                     │                     │
│        ┌─────┴─────┐                           │                     │
│        ▼           ▼                           │ Loop                │
│  'to buy'     'to sell'                        │                     │
│        │           │                           │                     │
│        ▼           ▼                           │                     │
│  toBuyProcess() toSellProcess()                │                     │
│        │           │                           │                     │
│        └─────┬─────┘                           │                     │
│              │                                 │                     │
│              ▼                                 │                     │
│      $processedTasks[]────────────────────────►┘                     │
│                                                                       │
│  ┌────────────────────────────────────────────────────────────┐      │
│  │                    POST-PROCESSING                          │      │
│  │                                                              │      │
│  │  saveCryptoPrice($processedTasks)   ──► historical_price    │      │
│  │  saveCryptoLadder($processedTasks)  ──► historical_ladder   │      │
│  │  saveDifferenceHistory($processedTasks) ──► difference_history │   │
│  │                                                              │      │
│  └──────────────────────────────────────────────────────────────┘      │
│                                                                         │
│  Return JSON Response with all processed task results                  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

**Key File**: `app/Http/Controllers/Api/CheckProcessController.php`

### 3.2 "To Buy" Logic Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         TO BUY PROCESS FLOW                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Input: Task with taskType = 'to buy'                                  │
│                                                                         │
│  Task Fields Used:                                                     │
│  • toBuyCurrentCashValue (PHP amount to invest)                        │
│  • toBuyStartingCoinValue (BTC baseline)                               │
│  • toBuyMinThreshold (minimum PHP difference to notify)                │
│  • toBuyIntervalThreshold (interval for notification grouping)         │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    CALCULATION FLOW                              │   │
│  │                                                                   │   │
│  │  1. Get BTC Price from CoinGecko API                             │   │
│  │     ──► btcToPhpRate = PHP price per 1 BTC                       │   │
│  │                                                                   │   │
│  │  2. Calculate potential BTC purchase                              │   │
│  │     toBuyCoinAmount = toBuyCurrentCashValue / btcToPhpRate       │   │
│  │                                                                   │   │
│  │  3. Calculate CoinsPH fee (0.5%)                                  │   │
│  │     coinsChargeInBtc = (toBuyCurrentCashValue * 0.005) / rate    │   │
│  │                                                                   │   │
│  │  4. Calculate final BTC after fee                                 │   │
│  │     finalBtcAmount = toBuyCoinAmount - coinsChargeInBtc          │   │
│  │                                                                   │   │
│  │  5. Calculate difference from starting value                      │   │
│  │     btcDifference = finalBtcAmount - toBuyStartingCoinValue      │   │
│  │                                                                   │   │
│  │  6. Convert difference to PHP                                     │   │
│  │     differenceInPhp = btcDifference * btcToPhpRate               │   │
│  │                                                                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    THRESHOLD CHECK                               │   │
│  │                                                                   │   │
│  │  if (differenceInPhp > toBuyMinThreshold) {                      │   │
│  │      thresholdQuotient = floor(differenceInPhp / intervalThreshold) │
│  │                                                                   │   │
│  │      // Check if already notified today at this level            │   │
│  │      ThresholdTask::where([                                      │   │
│  │          'usersId' => task.usersId,                              │   │
│  │          'taskId' => task.id,                                    │   │
│  │          'thresholdQuotient' => thresholdQuotient,               │   │
│  │          'date' => today                                         │   │
│  │      ])->first()                                                 │   │
│  │                                                                   │   │
│  │      if NOT exists:                                              │   │
│  │          - Create ThresholdTask record                           │   │
│  │          - sendNotificationToBuy() ──► Send Email                │   │
│  │  }                                                               │   │
│  │                                                                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.3 "To Sell" Logic Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         TO SELL PROCESS FLOW                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Input: Task with taskType = 'to sell'                                 │
│                                                                         │
│  Task Fields Used:                                                     │
│  • currentCoinValue (BTC amount held)                                  │
│  • startingPhpValue (PHP baseline)                                     │
│  • minThreshold (minimum PHP difference to notify)                     │
│  • intervalThreshold (interval for notification grouping)              │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    CALCULATION FLOW                              │   │
│  │                                                                   │   │
│  │  1. Get BTC Price from CoinGecko API                             │   │
│  │     ──► btcToPhpRate = PHP price per 1 BTC                       │   │
│  │                                                                   │   │
│  │  2. Calculate potential PHP value                                 │   │
│  │     potentialValue = currentCoinValue * btcToPhpRate             │   │
│  │                                                                   │   │
│  │  3. Calculate CoinsPH fee (0.5%)                                  │   │
│  │     coinsCharge = potentialValue * 0.005                         │   │
│  │                                                                   │   │
│  │  4. Calculate final PHP after fee                                 │   │
│  │     finalAmount = potentialValue - coinsCharge                   │   │
│  │                                                                   │   │
│  │  5. Calculate difference from starting value                      │   │
│  │     difference = finalAmount - startingPhpValue                  │   │
│  │                                                                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    THRESHOLD CHECK                               │   │
│  │                                                                   │   │
│  │  if (difference > minThreshold) {                                │   │
│  │      thresholdQuotient = floor(difference / intervalThreshold)   │   │
│  │                                                                   │   │
│  │      // Check if already notified today at this level            │   │
│  │      ThresholdTask::where([...same as buy...])->first()          │   │
│  │                                                                   │   │
│  │      if NOT exists:                                              │   │
│  │          - Create ThresholdTask record                           │   │
│  │          - sendNotificationToSell() ──► Send Email               │   │
│  │  }                                                               │   │
│  │                                                                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.4 Historical Data Saving Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    HISTORICAL DATA PERSISTENCE                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  After all tasks processed:                                            │
│                                                                         │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │  saveBTCPrice()                                                 │    │
│  │                                                                  │    │
│  │  • Fetch BTC price from CoinGecko                               │    │
│  │  • INSERT INTO historical_price (coinType, valueInPhp)          │    │
│  │                                                                  │    │
│  │  Result: New row every 5 minutes (via cron)                     │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │  saveBTCLadder()                                                │    │
│  │                                                                  │    │
│  │  • Get last 72 historical_price records                         │    │
│  │  • Calculate percentage changes from current price              │    │
│  │  • Build ladder structure with diffs                            │    │
│  │  • INSERT INTO historical_ladder (data JSON)                    │    │
│  │                                                                  │    │
│  │  Ladder Data Structure:                                         │    │
│  │  {                                                              │    │
│  │    "id": "btc-2024-01-15T10:30:00Z",                           │    │
│  │    "values": [0.00123, 0.00234, ...],  // 72 pct changes       │    │
│  │    "metadata": {                                                │    │
│  │      "asset": "BTC",                                           │    │
│  │      "interval_min": 5,                                        │    │
│  │      "ts": "2024-01-15T10:30:00Z",                             │    │
│  │      "close": 6490512.70,                                      │    │
│  │      "diffs": {                                                │    │
│  │        "5": {"ts": "...", "abs": 1234.56, "pct": 0.0012},     │    │
│  │        "10": {...},                                            │    │
│  │        ...                                                     │    │
│  │      }                                                         │    │
│  │    }                                                           │    │
│  │  }                                                              │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │  saveDifferenceHistory()                                        │    │
│  │                                                                  │    │
│  │  • For each processed task result:                              │    │
│  │  • Extract task data and calculate cash difference              │    │
│  │  • INSERT INTO difference_history:                              │    │
│  │    - usersId                                                    │    │
│  │    - taskType ('to buy' or 'to sell')                          │    │
│  │    - toSellCurrentCoinValue / toBuyCurrentCashValue            │    │
│  │    - toSellStartingPhpValue / toBuyStartingCoinValue           │    │
│  │    - cashDifference                                            │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 4. External API Integrations

### 4.1 CoinGecko API Integration

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      COINGECKO API INTEGRATION                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Endpoint: https://api.coingecko.com/api/v3/simple/price               │
│                                                                         │
│  Parameters:                                                           │
│  • ids=bitcoin                                                         │
│  • vs_currencies=php                                                   │
│  • x_cg_demo_api_key={API_KEY}                                        │
│                                                                         │
│  API Key: CG-k3ZFMjdtUQf6a1XNNAA4ajc2 (Demo tier)                     │
│                                                                         │
│  Response Format:                                                      │
│  {                                                                     │
│    "bitcoin": {                                                        │
│      "php": 6490512.70                                                │
│    }                                                                   │
│  }                                                                     │
│                                                                         │
│  Usage in Code:                                                        │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │  private function getBtcPriceFromCoinGecko(): array              │ │
│  │  {                                                                │ │
│  │      $response = file_get_contents($url);                        │ │
│  │      $data = json_decode($response, true);                       │ │
│  │      return [                                                    │ │
│  │          'btc_to_php_rate' => $data['bitcoin']['php'],          │ │
│  │          'coingecko_data' => $data                               │ │
│  │      ];                                                          │ │
│  │  }                                                                │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  Error Handling:                                                       │
│  • Catches exceptions from file_get_contents                          │
│  • Returns error array if API fails                                   │
│  • Logs errors to Laravel log                                         │
│                                                                         │
│  Call Frequency: Every API request (5-minute cron interval)           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4.2 CoinsPH Fee Calculation

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      COINSPH FEE CALCULATIONS                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Fee Rate: 0.5% (0.005)                                                │
│                                                                         │
│  For BUYING:                                                           │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │  chargeInPhp = cashValue * 0.005                                 │ │
│  │  chargeInBtc = chargeInPhp / (cashValue / btcAmount)            │ │
│  │                                                                   │ │
│  │  Final BTC = purchasedBtc - chargeInBtc                          │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  For SELLING:                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │  charge = potentialValue * 0.005                                 │ │
│  │                                                                   │ │
│  │  Final PHP = potentialValue - charge                             │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Notification System Flow

### 5.1 Email Notification Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      EMAIL NOTIFICATION SYSTEM                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  PER-USER SMTP CONFIGURATION                                    │   │
│  │                                                                   │   │
│  │  notification_email table:                                       │   │
│  │  • usersId ──► Links to users.id                                │   │
│  │  • email ──► SMTP sender email                                  │   │
│  │  • password ──► SMTP password (stored plain!)                   │   │
│  │  • smtp_host ──► e.g., "smtp.gmail.com"                         │   │
│  │  • smtp_port ──► e.g., 587                                      │   │
│  │                                                                   │   │
│  │  notification_receiver table:                                    │   │
│  │  • usersId ──► Links to users.id                                │   │
│  │  • emailRecipient ──► Where to send alerts                      │   │
│  │                                                                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  NOTIFICATION SEND FLOW                                         │   │
│  │                                                                   │   │
│  │  1. Get NotificationEmail for task's usersId                    │   │
│  │  2. Get NotificationReceiver for task's usersId                 │   │
│  │  3. Validate SMTP configuration exists                          │   │
│  │  4. Dynamically configure Laravel mailer:                       │   │
│  │                                                                   │   │
│  │     config(['mail.mailers.smtp' => [                            │   │
│  │         'transport' => 'smtp',                                  │   │
│  │         'host' => $notificationEmail->smtp_host,                │   │
│  │         'port' => $notificationEmail->smtp_port,                │   │
│  │         'username' => $notificationEmail->email,                │   │
│  │         'password' => $notificationEmail->password,             │   │
│  │         'encryption' => 'tls',                                  │   │
│  │     ]]);                                                        │   │
│  │                                                                   │   │
│  │  5. Send email via Mail::raw()                                  │   │
│  │  6. Save to notification_history table                          │   │
│  │                                                                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  Email Templates:                                                      │
│                                                                         │
│  BUY Alert:                                                            │
│  ┌──────────────────────────────────────────────────────────────┐     │
│  │ Subject: Buy Crypto To Earn - {difference}                   │     │
│  │ Body: The earning you will get if you buy now is {diff} PHP. │     │
│  └──────────────────────────────────────────────────────────────┘     │
│                                                                         │
│  SELL Alert:                                                           │
│  ┌──────────────────────────────────────────────────────────────┐     │
│  │ Subject: Sell Your Crypto To Earn - {difference}             │     │
│  │ Body: The earning you will get if you sell now is {diff} PHP.│     │
│  └──────────────────────────────────────────────────────────────┘     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.2 Threshold Deduplication Logic

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    THRESHOLD DEDUPLICATION                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Purpose: Prevent sending duplicate notifications for the same         │
│           threshold level on the same day.                             │
│                                                                         │
│  threshold_task table:                                                 │
│  • usersId                                                             │
│  • taskId                                                              │
│  • thresholdQuotient ──► floor(difference / intervalThreshold)        │
│  • date                                                                │
│                                                                         │
│  Logic:                                                                │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │  Example: intervalThreshold = 100 PHP                            │ │
│  │                                                                   │ │
│  │  If difference = 250 PHP:                                        │ │
│  │  • thresholdQuotient = floor(250 / 100) = 2                     │ │
│  │                                                                   │ │
│  │  If difference = 350 PHP:                                        │ │
│  │  • thresholdQuotient = floor(350 / 100) = 3                     │ │
│  │                                                                   │ │
│  │  Notification sent only when:                                    │ │
│  │  • New quotient level reached today                              │ │
│  │  • No existing record for (usersId, taskId, quotient, date)     │ │
│  │                                                                   │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 6. E-commerce Module Logic Flows

### 6.1 Product Management CRUD Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    E-COMMERCE PRODUCT CRUD                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  LIST PRODUCTS                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Route: GET /ecom-products                                      │   │
│  │  Controller: ProductsController@index                           │   │
│  │                                                                   │   │
│  │  Query: EcomProduct::with('variants')                           │   │
│  │         ->where('deleteStatus', 1)                              │   │
│  │         ->orderBy('created_at', 'desc')                         │   │
│  │         ->get()                                                  │   │
│  │                                                                   │   │
│  │  View: ecommerce/products/index.blade.php                       │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  CREATE PRODUCT                                                        │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Route: POST /ecom-products-add                                 │   │
│  │  Controller: ProductsController@store                           │   │
│  │                                                                   │   │
│  │  Validation:                                                     │   │
│  │  • productName: required|string|max:255                         │   │
│  │  • productDescription: required|string                          │   │
│  │  • productStore: required|string                                │   │
│  │  • productType: required|in:ship,digital                        │   │
│  │                                                                   │   │
│  │  Insert: EcomProduct::create([                                  │   │
│  │      ...fields,                                                  │   │
│  │      'isActive' => 1,                                           │   │
│  │      'deleteStatus' => 1                                        │   │
│  │  ])                                                              │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  SOFT DELETE                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Route: DELETE /ecom-products/{id}                              │   │
│  │                                                                   │   │
│  │  Update: $product->update(['deleteStatus' => 0])                │   │
│  │                                                                   │   │
│  │  NOTE: Uses deleteStatus=0 (integer) NOT delete_status='deleted'│   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Product Variant Hierarchy

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PRODUCT VARIANT HIERARCHY                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Product (ecom_products)                                               │
│    │                                                                    │
│    ├──► Variants (ecom_products_variants)                              │
│    │       │                                                            │
│    │       ├──► Images (ecom_products_variants_images)                 │
│    │       │       └─ imageOrder for sorting                           │
│    │       │                                                            │
│    │       ├──► Videos (ecom_products_variants_videos)                 │
│    │       │       └─ videoOrder for sorting                           │
│    │       │                                                            │
│    │       ├──► Shipping Assignments (ecom_products_variants_shipping) │
│    │       │       └─ Junction to shipping methods                     │
│    │       │                                                            │
│    │       └──► Tags (ecom_products_variants_tags)                     │
│    │               └─ Junction to axis_tags (triggers)                 │
│    │                                                                    │
│    └──► Relationships in Model:                                        │
│        • hasMany('variants', 'ecomProductsId')                         │
│        • variants->hasMany('images', 'ecomVariantsId')                 │
│        • variants->belongsToMany('shipping')                           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.3 Order Creation Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    ORDER CREATION FLOW                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Route: POST /ecom-orders-custom-add                                   │
│  Controller: OrdersCustomAddController@store                           │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  STEP 1: Product Selection                                      │   │
│  │  • getProducts() ──► List available products                    │   │
│  │  • getProductVariants() ──► Get variants for selected product   │   │
│  │  • getVariantDetails() ──► Get pricing, stock info              │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  STEP 2: Client Information                                     │   │
│  │  • checkClientPhone() ──► Check if existing client              │   │
│  │  • saveClient() ──► Create new client if needed                 │   │
│  │  • getClients() ──► List existing clients for selection         │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  STEP 3: Shipping Configuration                                 │   │
│  │  • getPhilippineProvinces() ──► Province list                   │   │
│  │  • getPhilippineMunicipalities() ──► Cities/towns               │   │
│  │  • calculateShipping() ──► Calculate shipping fee               │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  STEP 4: Order Finalization                                     │   │
│  │                                                                   │   │
│  │  Transaction Block:                                              │   │
│  │  1. Generate unique orderNumber                                  │   │
│  │  2. INSERT INTO ecom_orders (main order record)                 │   │
│  │  3. INSERT INTO ecom_orders_client (customer snapshot)          │   │
│  │  4. INSERT INTO ecom_orders_products (product snapshots)        │   │
│  │  5. INSERT INTO ecom_orders_shipping (address snapshot)         │   │
│  │  6. UPDATE variant stocksAvailable (decrement)                  │   │
│  │                                                                   │   │
│  │  Note: Product/variant data is SNAPSHOT, not referenced         │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.4 Shipping Price Calculation

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    SHIPPING CALCULATION LOGIC                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Input: variant, province, quantity                                    │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  1. Get shipping methods assigned to variant                    │   │
│  │     via ecom_products_variants_shipping                         │   │
│  │                                                                   │   │
│  │  2. For each shipping method:                                   │   │
│  │     a. Check ecom_products_shipping_options                     │   │
│  │        WHERE provinceTarget = selected_province                 │   │
│  │                                                                   │   │
│  │     b. If province option exists:                               │   │
│  │        - Use shippingPrice from option                          │   │
│  │        - Check maxQuantity against order quantity               │   │
│  │                                                                   │   │
│  │     c. If no province option:                                   │   │
│  │        - Use defaultPrice from shipping method                  │   │
│  │        - Check defaultMaxQuantity                               │   │
│  │                                                                   │   │
│  │  3. Return available shipping options with prices               │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 7. Ani-Senso Course Module Logic Flows

### 7.1 Course Content Hierarchy

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    COURSE CONTENT HIERARCHY                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Course (as_courses)                                                   │
│    │                                                                    │
│    ├──► Chapters (as_courses_chapters)                                 │
│    │       │   └─ chapterOrder for drag-drop sorting                   │
│    │       │                                                            │
│    │       └──► Topics (as_courses_topics)                             │
│    │               │   └─ topicsOrder for sorting                      │
│    │               │                                                    │
│    │               └──► Resources (as_topics_resources)                │
│    │                       └─ resourcesOrder for sorting               │
│    │                                                                    │
│    └──► Access Tags (via axis_tags)                                    │
│            └─ For access control                                       │
│                                                                         │
│  Ordering Flow:                                                        │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  updateChapterOrder(Request $request)                           │   │
│  │  • Receives: chapters[{id, order}, ...]                         │   │
│  │  • Updates: chapterOrder for each chapter ID                    │   │
│  │  • Uses: Dragula.js on frontend for drag-drop                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 7.2 Content CRUD Operations

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    ANI-SENSO CRUD PATTERNS                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  CREATE COURSE                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Route: POST /anisenso-courses                                  │   │
│  │                                                                   │   │
│  │  Validation:                                                     │   │
│  │  • courseName: required|string|max:255                          │   │
│  │  • courseSmallDescription: required|string|max:500              │   │
│  │  • courseBigDescription: required|string                        │   │
│  │  • courseImage: required|image|mimes:jpeg,png,jpg,gif|max:5120  │   │
│  │                                                                   │   │
│  │  Image Upload:                                                   │   │
│  │  • Move to: public/images/courses/                              │   │
│  │  • Filename: time()_uniqid().extension                          │   │
│  │                                                                   │   │
│  │  Insert with:                                                    │   │
│  │  • isActive = true                                              │   │
│  │  • deleteStatus = true                                          │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ADD CHAPTER                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Route: POST /anisenso-courses-chapters                         │   │
│  │                                                                   │   │
│  │  Order Assignment:                                               │   │
│  │  $maxOrder = AsCourseChapter::where('asCoursesId', $courseId)   │   │
│  │              ->where('deleteStatus', 1)                         │   │
│  │              ->max('chapterOrder') ?? 0;                        │   │
│  │  $chapter->chapterOrder = $maxOrder + 1;                        │   │
│  │                                                                   │   │
│  │  Insert with: deleteStatus = 1 (integer!)                       │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  SOFT DELETE DIFFERENCES                                               │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Courses:                                                        │   │
│  │  • deleteStatus = false (boolean as string in some cases)       │   │
│  │                                                                   │   │
│  │  Chapters/Topics:                                               │   │
│  │  • deleteStatus = 0 (integer)                                   │   │
│  │                                                                   │   │
│  │  INCONSISTENCY WARNING: Active checks vary:                     │   │
│  │  • Courses: ->where('deleteStatus', true)                       │   │
│  │  • Chapters: ->where('deleteStatus', 1)                         │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 7.3 File Upload Flows

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    FILE UPLOAD HANDLING                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  COURSE/CHAPTER IMAGES                                                 │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Storage: public/images/courses/ or public/images/chapters/     │   │
│  │  Naming: time() . '_' . uniqid() . '.' . extension             │   │
│  │  Max Size: 5120KB (5MB)                                         │   │
│  │  Types: jpeg, png, jpg, gif                                     │   │
│  │                                                                   │   │
│  │  On Update:                                                      │   │
│  │  • Check if old file exists                                     │   │
│  │  • unlink(public_path($oldPath))                                │   │
│  │  • Upload new file                                              │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  TOPIC RESOURCES                                                       │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Route: POST /anisenso-courses-topics-resources-upload          │   │
│  │  Storage: public/uploads/resources/                             │   │
│  │  Max Size: 51200KB (50MB)                                       │   │
│  │  Types: pdf, doc, docx, xls, xlsx, ppt, pptx, zip, rar         │   │
│  │                                                                   │   │
│  │  Database: as_topics_resources                                  │   │
│  │  • asTopicsId (FK)                                              │   │
│  │  • fileName (original name)                                     │   │
│  │  • fileUrl (storage path)                                       │   │
│  │  • resourcesOrder (for sorting)                                 │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  TINYMCE IMAGE UPLOADS                                                 │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Route: POST /upload-image                                      │   │
│  │  Storage: public/images/topics/                                 │   │
│  │  Max Size: 10240KB (10MB)                                       │   │
│  │  Types: jpeg, png, jpg, gif                                     │   │
│  │                                                                   │   │
│  │  Response Format (for TinyMCE):                                 │   │
│  │  { "location": "https://domain.com/images/topics/xxx.jpg" }    │   │
│  │                                                                   │   │
│  │  Also saves to: as_image_library table                          │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 8. Income Logger Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    INCOME LOGGER FLOW                                   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Purpose: Manual logging of crypto trading income                      │
│                                                                         │
│  CREATE INCOME LOG                                                     │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Route: POST /crypto-income-logger-add                          │   │
│  │  Controller: CryptoIncomeLoggerController@store                 │   │
│  │                                                                   │   │
│  │  Input Fields:                                                   │   │
│  │  • taskCoin: required|string|max:10                             │   │
│  │  • taskType: required|in:to buy,to sell                         │   │
│  │  • transactionDate: required|date                               │   │
│  │  • transactionTime: required|date_format:H:i                    │   │
│  │  • originalPhpValue: required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/ │
│  │  • newPhpValue: required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/│   │
│  │                                                                   │   │
│  │  Processing:                                                     │   │
│  │  • Combine date + time into transactionDateTime                 │   │
│  │  • Set usersId = Auth::user()->id                               │   │
│  │  • Set delete_status = 'active'                                 │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  LIST WITH FILTERS                                                     │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  Route: GET /crypto-income-logger                               │   │
│  │                                                                   │   │
│  │  Available Filters:                                              │   │
│  │  • start_date, end_date (date range)                            │   │
│  │  • task_type ('to buy' or 'to sell')                            │   │
│  │  • coin_type (BTC, ETH, etc.)                                   │   │
│  │                                                                   │   │
│  │  Aggregations Calculated:                                        │   │
│  │  • totalToBuyDifference (all time, all buy transactions)       │   │
│  │  • totalToSellDifference (all time, all sell transactions)     │   │
│  │  • filteredToBuyDifference (filtered buy transactions)         │   │
│  │  • filteredToSellDifference (filtered sell transactions)       │   │
│  │                                                                   │   │
│  │  Formula: sum(newPhpValue - originalPhpValue)                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 9. Data Flow Diagrams

### 9.1 Crypto Module Data Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    CRYPTO MODULE DATA FLOW                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  External                Internal                   Storage             │
│  ┌─────────┐            ┌─────────────────────┐    ┌─────────────────┐ │
│  │CoinGecko│───────────►│CheckProcessController│───►│historical_price │ │
│  │   API   │ BTC Price  │                     │    │                 │ │
│  └─────────┘            │ ┌─────────────────┐ │    ├─────────────────┤ │
│                         │ │Process Buy/Sell │ │───►│historical_ladder│ │
│  ┌─────────┐            │ │Calculate Diff   │ │    │                 │ │
│  │  task   │───────────►│ │Check Threshold  │ │    ├─────────────────┤ │
│  │ table   │ Task Data  │ └────────┬────────┘ │───►│difference_history│
│  └─────────┘            │          │          │    │                 │ │
│                         │          ▼          │    ├─────────────────┤ │
│  ┌─────────┐            │ ┌─────────────────┐ │───►│threshold_task   │ │
│  │threshold│◄───────────│ │Send Notification│ │    │                 │ │
│  │  _task  │ Dedup Check│ └────────┬────────┘ │    ├─────────────────┤ │
│  └─────────┘            │          │          │───►│notification_    │ │
│                         │          ▼          │    │     history     │ │
│  ┌─────────┐            │ ┌─────────────────┐ │    └─────────────────┘ │
│  │notif_   │───────────►│ │Configure SMTP   │ │                        │
│  │ email   │ SMTP Config│ │Send Email       │ │                        │
│  └─────────┘            │ └─────────────────┘ │                        │
│                         │          │          │                        │
│  ┌─────────┐            │          ▼          │                        │
│  │notif_   │───────────►│   [Email Sent]     │                        │
│  │receiver │ Recipient  └─────────────────────┘                        │
│  └─────────┘                                                           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 9.2 E-commerce Order Data Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    E-COMMERCE ORDER DATA FLOW                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Source Tables                Transformation            Target Tables  │
│  ┌─────────────────┐         ┌─────────────┐          ┌─────────────┐ │
│  │ ecom_products   │────────►│             │─────────►│ ecom_orders │ │
│  │                 │ Lookup  │   Order     │ Generate │ (main)      │ │
│  └─────────────────┘         │   Creation  │ orderNum │             │ │
│                              │   Process   │          ├─────────────┤ │
│  ┌─────────────────┐         │             │─────────►│ orders_     │ │
│  │ ecom_products_  │────────►│ Snapshot    │ Copy     │ products    │ │
│  │ variants        │ Price   │ Product     │ Data     │ (snapshot)  │ │
│  └─────────────────┘ Stock   │ Info        │          │             │ │
│                              │             │          ├─────────────┤ │
│  ┌─────────────────┐         │             │─────────►│ orders_     │ │
│  │ clients_all_    │────────►│ Lookup or   │ Copy     │ client      │ │
│  │ database        │ Exist?  │ Create New  │ Info     │ (snapshot)  │ │
│  └─────────────────┘         │ Client      │          │             │ │
│                              │             │          ├─────────────┤ │
│  ┌─────────────────┐         │             │─────────►│ orders_     │ │
│  │ shipping_       │────────►│ Calculate   │ Save     │ shipping    │ │
│  │ options         │ Fee     │ Shipping    │ Address  │ (snapshot)  │ │
│  └─────────────────┘         └─────────────┘          └─────────────┘ │
│                                    │                                   │
│                                    ▼                                   │
│                         ┌─────────────────┐                           │
│                         │ UPDATE variant  │                           │
│                         │ stocksAvailable │                           │
│                         │ (decrement)     │                           │
│                         └─────────────────┘                           │
│                                                                         │
│  Note: Order tables store SNAPSHOTS, not references.                   │
│  This preserves historical accuracy if products change.                │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 10. Common Query Patterns by Module

### 10.1 User-Scoped Queries (Crypto/Income)

```php
// Standard pattern for user-specific data
$data = Model::active()
    ->forUser(Auth::id())
    ->orderBy('created_at', 'desc')
    ->get();

// With date filtering
$data = Model::active()
    ->forUser(Auth::id())
    ->whereBetween('transactionDateTime', [$start, $end])
    ->get();

// Aggregation with filters
$total = Model::active()
    ->forUser($userId)
    ->byTaskType('to buy')
    ->get()
    ->sum(fn($item) => $item->newPhpValue - $item->originalPhpValue);
```

### 10.2 E-commerce Queries

```php
// Products with variants (different soft delete!)
$products = EcomProduct::with(['variants' => function($q) {
    $q->where('deleteStatus', 1);
}])
->where('deleteStatus', 1)
->where('isActive', 1)
->get();

// Shipping options for province
$options = EcomProductsShippingOptions::where('shippingId', $shippingId)
    ->where('provinceTarget', $province)
    ->where('deleteStatus', 1)
    ->first();
```

### 10.3 Ani-Senso Queries

```php
// Courses (deleteStatus as boolean/string!)
$courses = AsCourse::where('isActive', true)
    ->where('deleteStatus', true)
    ->get();

// Chapters ordered
$chapters = AsCourseChapter::where('asCoursesId', $courseId)
    ->where('deleteStatus', 1)  // Integer here!
    ->orderBy('chapterOrder', 'ASC')
    ->get();

// Topics with eager loading
$chapters = AsCourseChapter::where('asCoursesId', $courseId)
    ->where('deleteStatus', 1)
    ->with(['topics' => function($q) {
        $q->where('deleteStatus', 1)
          ->orderBy('topicsOrder', 'ASC');
    }])
    ->orderBy('chapterOrder', 'ASC')
    ->get();
```

---

## 11. Critical Business Rules

### 11.1 Crypto Module Rules

| Rule | Implementation | Location |
|------|---------------|----------|
| Notify only on new threshold level | ThresholdTask deduplication check | `CheckProcessController::checkToBuyThresholdTask()` |
| One notification per quotient per day | date + quotient uniqueness | `threshold_task` table |
| CoinsPH fee is 0.5% | Hardcoded constant | `getCoinsCharge()`, `getCoinsChargeForBuy()` |
| Per-user SMTP configuration | Dynamic mail config | `sendNotificationToBuy()`, `sendNotificationToSell()` |
| All crypto data is user-scoped | `usersId` filter | All crypto queries |

### 11.2 E-commerce Rules

| Rule | Implementation | Location |
|------|---------------|----------|
| Order snapshots product data | Copy, not reference | `OrdersCustomAddController::store()` |
| Stock decrement on order | Update variant after order | Order creation flow |
| Province-specific shipping | Shipping options lookup | `calculateShipping()` |
| Soft delete uses integer 1/0 | `deleteStatus = 1` for active | All e-commerce models |

### 11.3 Ani-Senso Rules

| Rule | Implementation | Location |
|------|---------------|----------|
| Content ordering via drag-drop | `*Order` columns | chapters, topics, resources |
| Max order auto-increment | `max() + 1` on create | `storeChapter()`, `storeTopic()` |
| Image cleanup on update | `unlink()` old file | `update()` methods |
| Mixed deleteStatus types | Boolean/Integer | See inconsistency warnings |

---

## 12. Security Considerations

### 12.1 Authentication Points

| Entry Point | Protection | Notes |
|-------------|------------|-------|
| Web routes | `->middleware('auth')` | Redirects to login |
| API `/check-process` | Token-based via `access-token` table | No expiration |
| File uploads | Auth middleware + validation | Size/type limits |

### 12.2 Authorization Gaps (Known Issues)

1. **No role-based access control** - All authenticated users have same permissions
2. **No ownership validation in some e-commerce routes** - Products not user-scoped
3. **API token has no expiration** - Security risk
4. **SMTP passwords stored in plain text** - `notification_email.password`

### 12.3 Input Validation

- Controllers use `Validator::make()` with custom messages
- File uploads have MIME type and size validation
- Decimal values use regex for precision: `/^\d+(\.\d{1,2})?$/`

---

## 13. Integration Dependencies

### 13.1 External Services

| Service | Purpose | Failure Handling |
|---------|---------|------------------|
| CoinGecko API | BTC price data | Returns error array, logged |
| User SMTP servers | Email notifications | Catches exception, logs error |

### 13.2 Internal Dependencies

```
CheckProcessController depends on:
├── Task model (data source)
├── AccessToken model (authentication)
├── ThresholdTask model (deduplication)
├── NotificationEmail model (SMTP config)
├── NotificationReceiver model (recipients)
├── NotificationHistory model (logging)
├── HistoricalPrice model (persistence)
├── HistoricalLadder model (persistence)
├── DifferenceHistory model (persistence)
└── CoinGecko API (external)
```

---

## 14. Troubleshooting Guide

### 14.1 Common Issues

| Symptom | Likely Cause | Solution |
|---------|--------------|----------|
| No notifications sent | Missing `notification_email` record | Check user has SMTP config |
| Duplicate notifications | `threshold_task` not being created | Check date format matches |
| Wrong soft delete behavior | Module uses different pattern | Check codebase-architect for correct pattern |
| Empty product list | `deleteStatus` value mismatch | Use `1` not `'active'` for e-commerce |
| Chapters not ordered | Missing `orderBy('chapterOrder')` | Add to query |

### 14.2 Debugging Points

```php
// Enable query logging
DB::enableQueryLog();
// ... execute queries
Log::info(DB::getQueryLog());

// Check notification flow
Log::info("Task usersId: {$task->usersId}");
Log::info("Notification email found: " . ($notificationEmail ? 'YES' : 'NO'));
Log::info("SMTP config: " . json_encode($notificationEmail->toArray()));
```

---

## 15. Quick Reference

### Controller → Logic Mapping

| Controller | Primary Logic | Key Methods |
|------------|---------------|-------------|
| `CheckProcessController` | Crypto price checking, notifications | `checkProcess()`, `toBuyProcess()`, `toSellProcess()` |
| `CryptoIncomeLoggerController` | Income logging CRUD | `index()`, `store()`, `destroy()` |
| `ProductsController` | E-commerce product management | Full CRUD + variants, images, shipping |
| `OrdersCustomAddController` | Order creation wizard | Multi-step order creation |
| `AniSensoCourseController` | Course content management | Courses, chapters, topics, resources |

### API Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/check-process` | GET | Trigger crypto price check (cron) |
| `/api/user` | GET | Get authenticated user (Sanctum) |

### Cron Jobs (Expected)

| Schedule | Endpoint | Purpose |
|----------|----------|---------|
| Every 5 min | `/api/check-process?token=xxx` | Update prices, check thresholds |

---

## 16. Related Skills & Agent Harmony

### 16.1 Skill Ecosystem

This skill is part of the DS AXIS architect skill system:

| Skill | Purpose | When to Reference |
|-------|---------|-------------------|
| **codebase-architect** | System structure, patterns | WHERE code lives, HOW to structure |
| **database-architect** | Database schema, relationships | WHAT data exists, table structures |
| **logic-architect** (this) | Business logic, data flows | HOW logic executes, API integrations |
| **ds-axis-agent-harmony** | Agent collaboration rules | Multi-agent development |
| **pact-skills-updater** | Keep skills synchronized | After logic changes |

### 16.2 Cross-References

- For code structure → See `codebase-architect.md`
- For database schemas → See `database-architect.md`
- For agent workflows → See `ds-axis-agent-harmony.md`

### 16.3 DS AXIS Context Reminder

This system is the **centralized admin backend** for Dragon Scale Web Company. Logic documented here serves administrative operations across all sub-ventures. Customer-facing frontends are separate systems that will consume data managed here.

---

*This document provides the logical flow layer of the DS AXIS system. Refer to `codebase-architect.md` for structural patterns, `database-architect.md` for data schema details, and `ds-axis-agent-harmony.md` for multi-agent collaboration guidelines.*
