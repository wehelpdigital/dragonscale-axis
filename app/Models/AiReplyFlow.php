<?php

namespace App\Models;

class AiReplyFlow extends BaseModel
{
    protected $table = 'ai_reply_flows';

    protected $fillable = [
        'usersId',
        'flowName',
        'flowDescription',
        'flowData',
        'personality',
        'sampleConversations',
        'priority',
        'isActive',
        'isDefault',
        'delete_status',
    ];

    protected $casts = [
        'flowData' => 'array',
        'sampleConversations' => 'array',
        'priority' => 'integer',
        'isActive' => 'boolean',
        'isDefault' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Node type constants for flow elements.
     */
    // Start node
    const NODE_START = 'start';

    // Query nodes
    const NODE_QUERY = 'query';
    const NODE_RAG_QUERY = 'rag_query';           // Unified RAG (Pinecone - files, websites, products)
    const NODE_ONLINE_QUERY = 'online_query';     // Web Search (Google Search via Gemini)
    const NODE_API_QUERY = 'api_query';           // External API calls

    // Conditional nodes
    const NODE_IF_ELSE_IMAGE = 'if_else_image';
    const NODE_IF_ELSE_QUERY = 'if_else_query';

    // Configuration nodes
    const NODE_PERSONALITY = 'personality';
    const NODE_THINKING_REPLY = 'thinking_reply';
    const NODE_BLOCKER = 'blocker';

    // Output node (end of flow)
    const NODE_OUTPUT = 'output';

    /**
     * Available merge fields for query nodes.
     * Note: {{output_nodeId}} is dynamically generated based on connected elements.
     */
    public static function getMergeFields(): array
    {
        return [
            '{{user_message}}' => 'User Message',
            '{{chat_history}}' => 'Chat History',
            '{{personality}}' => 'Personality Text',
        ];
    }

    /**
     * Get all available node types with metadata.
     */
    public static function getNodeTypes(): array
    {
        return [
            // Start
            self::NODE_START => [
                'label' => 'Start',
                'description' => 'Entry point - receives user message',
                'icon' => 'bx-play-circle',
                'color' => '#34c38f',
                'category' => 'start',
                'hasInput' => false,
                'hasOutput' => true,
                'hasBranching' => false,
            ],

            // Query nodes
            self::NODE_QUERY => [
                'label' => 'AI Query',
                'description' => 'Send a query to AI (Gemini/OpenAI/Claude)',
                'icon' => 'bx-message-square-dots',
                'color' => '#556ee6',
                'category' => 'query',
                'hasInput' => true,
                'hasOutput' => true,
                'hasBranching' => false,
            ],

            self::NODE_RAG_QUERY => [
                'label' => 'RAG Query',
                'description' => 'Query knowledge base (Files, Websites, Products)',
                'icon' => 'bx-data',
                'color' => '#f1b44c',
                'category' => 'query',
                'hasInput' => true,
                'hasOutput' => true,
                'hasBranching' => false,
            ],

            self::NODE_ONLINE_QUERY => [
                'label' => 'Web Search',
                'description' => 'Search the internet via Google',
                'icon' => 'bx-search-alt',
                'color' => '#8B5CF6',
                'category' => 'query',
                'hasInput' => true,
                'hasOutput' => true,
                'hasBranching' => false,
            ],

            self::NODE_API_QUERY => [
                'label' => 'API Query',
                'description' => 'Query an external API endpoint',
                'icon' => 'bx-code-alt',
                'color' => '#EC4899',
                'category' => 'query',
                'hasInput' => true,
                'hasOutput' => true,
                'hasBranching' => false,
            ],

            // Conditional nodes
            self::NODE_IF_ELSE_IMAGE => [
                'label' => 'If/Else (Image)',
                'description' => 'Check if message has images',
                'icon' => 'bx-image',
                'color' => '#0891B2',
                'category' => 'conditional',
                'hasInput' => true,
                'hasOutput' => true,
                'hasBranching' => true,
            ],

            self::NODE_IF_ELSE_QUERY => [
                'label' => 'If/Else (Query)',
                'description' => 'AI decides Yes or No path',
                'icon' => 'bx-git-compare',
                'color' => '#DC2626',
                'category' => 'conditional',
                'hasInput' => true,
                'hasOutput' => true,
                'hasBranching' => true,
            ],

            // Configuration nodes
            self::NODE_PERSONALITY => [
                'label' => 'Personality',
                'description' => 'Define AI personality & sample conversations',
                'icon' => 'bx-user-voice',
                'color' => '#9333EA',
                'category' => 'configuration',
                'hasInput' => true,
                'hasOutput' => true,
                'hasBranching' => false,
            ],

            self::NODE_THINKING_REPLY => [
                'label' => 'Thinking Reply',
                'description' => 'AI response while processing',
                'icon' => 'bx-loader-circle',
                'color' => '#F59E0B',
                'category' => 'configuration',
                'hasInput' => true,
                'hasOutput' => true,
                'hasBranching' => false,
            ],

            self::NODE_BLOCKER => [
                'label' => 'Blocker',
                'description' => 'Check if question is in scope before processing',
                'icon' => 'bx-shield-quarter',
                'color' => '#EF4444',
                'category' => 'configuration',
                'hasInput' => true,
                'hasOutput' => true,
                'hasBranching' => false,
            ],

            // Output node
            self::NODE_OUTPUT => [
                'label' => 'Output',
                'description' => 'End of flow - sends response',
                'icon' => 'bx-exit',
                'color' => '#059669',
                'category' => 'output',
                'hasInput' => true,
                'hasOutput' => false,
                'hasBranching' => false,
            ],
        ];
    }

    /**
     * Get node types grouped by category.
     */
    public static function getNodeTypesByCategory(): array
    {
        $types = self::getNodeTypes();
        $categories = [
            'start' => ['label' => 'Start', 'icon' => 'bx-play-circle'],
            'configuration' => ['label' => 'Configuration', 'icon' => 'bx-cog'],
            'query' => ['label' => 'Data Sources', 'icon' => 'bx-data'],
            'conditional' => ['label' => 'Conditional', 'icon' => 'bx-git-branch'],
            'output' => ['label' => 'Output', 'icon' => 'bx-exit'],
        ];

        $grouped = [];
        foreach ($categories as $catKey => $catInfo) {
            $grouped[$catKey] = [
                'label' => $catInfo['label'],
                'icon' => $catInfo['icon'],
                'nodes' => [],
            ];
        }

        foreach ($types as $type => $info) {
            $category = $info['category'];
            if (isset($grouped[$category])) {
                $grouped[$category]['nodes'][$type] = $info;
            }
        }

        return $grouped;
    }

    /**
     * Get or create the single reply flow for a user.
     */
    public static function getOrCreateForUser($userId): self
    {
        $flow = self::where('usersId', $userId)
            ->where('delete_status', 'active')
            ->first();

        if (!$flow) {
            $flow = self::create([
                'usersId' => $userId,
                'flowName' => 'Reply Flow',
                'flowDescription' => 'AI Reply Flow Configuration',
                'flowData' => [
                    'nodes' => [
                        [
                            'id' => 'node_start',
                            'type' => 'start',
                            'position' => ['x' => 300, 'y' => 50],
                            'data' => []
                        ]
                    ],
                    'connections' => [],
                    'nodeIdCounter' => 0
                ],
                'priority' => 0,
                'isActive' => true,
                'isDefault' => true,
                'delete_status' => 'active',
            ]);
        }

        return $flow;
    }

    /**
     * Scope: Active flows only.
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope: Filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope: Enabled flows only.
     */
    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Get the status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->isActive) {
            return '<span class="badge bg-success">Active</span>';
        }
        return '<span class="badge bg-secondary">Inactive</span>';
    }

    /**
     * Get node count from flow data.
     */
    public function getNodeCountAttribute(): int
    {
        if (!$this->flowData || !isset($this->flowData['nodes'])) {
            return 0;
        }
        return count($this->flowData['nodes']);
    }

    /**
     * Relationship: User who owns this flow.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }
}
