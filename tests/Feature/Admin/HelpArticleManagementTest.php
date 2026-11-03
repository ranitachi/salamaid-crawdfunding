<?php

namespace Tests\Feature\Admin;

use App\Models\HelpArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpArticleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_help_article(): void
    {
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($admin)->post(route('admin.help.store'), [
            'category' => 'Pembayaran', 'question' => 'Bagaimana pembayaran?', 'answer' => 'Gunakan Mayar.', 'sort_order' => 10, 'is_published' => 1,
        ])->assertRedirect();

        $article = HelpArticle::firstOrFail();
        $this->assertTrue($article->is_published);

        $this->actingAs($admin)->patch(route('admin.help.update', $article), [
            'category' => 'Pembayaran', 'question' => 'Bagaimana pembayaran diperbarui?', 'answer' => 'Jawaban baru.', 'sort_order' => 20,
        ])->assertRedirect();

        $this->assertDatabaseHas('help_articles', ['id' => $article->id, 'question' => 'Bagaimana pembayaran diperbarui?', 'is_published' => 0]);

        $this->actingAs($admin)->delete(route('admin.help.destroy', $article))->assertRedirect(route('admin.help.index'));
        $this->assertDatabaseMissing('help_articles', ['id' => $article->id]);
    }

    public function test_reviewer_cannot_manage_help_articles(): void
    {
        $reviewer = User::factory()->role('reviewer')->create();

        $this->actingAs($reviewer)->get(route('admin.help.index'))->assertForbidden();
    }

    public function test_public_help_only_shows_published_articles(): void
    {
        $user = User::factory()->create();
        HelpArticle::create(['category' => 'Publik', 'question' => 'Tampil?', 'answer' => 'Ya.', 'is_published' => true]);
        HelpArticle::create(['category' => 'Internal', 'question' => 'Rahasia?', 'answer' => 'Tidak.', 'is_published' => false]);

        $this->actingAs($user)->get(route('public.account.help'))
            ->assertOk()->assertSee('Tampil?')->assertDontSee('Rahasia?');
    }
}
