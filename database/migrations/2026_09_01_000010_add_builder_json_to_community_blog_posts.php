<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The blocks an article was built from.
 *
 * `body` holds the HTML the client app renders, and that is what a reader
 * sees. It is not something anybody can edit twice: once a builder has
 * flattened headings, pictures and pull quotes into markup, there is no way
 * back to the pieces. So the pieces are kept beside it.
 *
 * Only this app writes or reads this column — the client app renders `body`
 * and knows nothing about builders — which is why it can be added to a shared
 * table without the other side having to learn anything.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_blog_posts')
            || Schema::hasColumn('as_community_blog_posts', 'builderJson')) {
            return;
        }

        Schema::table('as_community_blog_posts', function (Blueprint $table) {
            $table->longText('builderJson')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('as_community_blog_posts')
            && Schema::hasColumn('as_community_blog_posts', 'builderJson')) {
            Schema::table('as_community_blog_posts', function (Blueprint $table) {
                $table->dropColumn('builderJson');
            });
        }
    }
};
