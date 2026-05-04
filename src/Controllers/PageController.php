<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\BlogPost;
use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\Page;

final class PageController extends BaseController
{
    public function show(Request $request, string $slug): Response
    {
        $page = Page::whereOne('slug = :s AND is_published = 1', ['s' => $slug]);
        if (!$page) {
            return $this->view('errors/404', [], 404);
        }
        return $this->view('pages/show', ['page' => $page]);
    }

    public function blogIndex(Request $request): Response
    {
        $page = max(1, (int) $request->input('page', 1));
        $pagination = BlogPost::paginate('is_published = 1', [], $page, 12, 'published_at DESC', '/blog');
        return $this->view('blog/index', ['pagination' => $pagination]);
    }

    public function blogPost(Request $request, string $slug): Response
    {
        $post = BlogPost::whereOne('slug = :s AND is_published = 1', ['s' => $slug]);
        if (!$post) {
            return $this->view('errors/404', [], 404);
        }
        return $this->view('blog/show', ['post' => $post]);
    }

    public function kbIndex(Request $request): Response
    {
        $cats = KbCategory::all('sort_order ASC, name ASC', 1000);
        return $this->view('kb/index', ['categories' => $cats]);
    }

    public function kbArticle(Request $request, string $slug): Response
    {
        $article = KbArticle::whereOne('slug = :s AND is_published = 1', ['s' => $slug]);
        if (!$article) {
            return $this->view('errors/404', [], 404);
        }
        return $this->view('kb/show', ['article' => $article]);
    }
}
