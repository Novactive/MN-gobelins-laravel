<?php

namespace App\Http\Controllers;

use A17\Twill\Models\Feature;
use App\Models\Section;
use App\Repositories\ArticleRepository;
use Illuminate\Http\Request;

class ArticleController extends Controller
{

    public function __construct(ArticleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function show($slug)
    {
        $article = $this->repository->forSlug($slug);
        abort_unless($article, 404, 'Article ');
        return view('site.article', ['item' => $article]);
    }

    public function home()
    {
        $featured_primary = Feature::forBucket('home_primary_features');
        $featured_secondary = Feature::forBucket('home_secondary_features');
        $sections = Section::published()->orderBy('position')->get();
        $section_articles = [];
        $sections->each(function ($s) use (&$section_articles) {
            $section_articles[$s->slug] = $this->repository->inSection($s);
        });

        return view('site.article_home', [
            'featured_primary' => $featured_primary,
            'featured_secondary' => $featured_secondary,
            'sections' => $sections,
            'section_articles' => $section_articles,
        ]);
    }

    public function recent(Request $request)
    {
        $articles = $this->repository->byRecent();
        abort_if($articles->isEmpty(), 404, "Aucun contenu disponible");
        return view('site.article_listing', [
            'articles' => $articles,
        ]);
    }

    function list(Request $request, $slug) {
        if ($request->route()->named('articles.by_tag')) {
            $articles = $this->repository->byTag($slug);
        } elseif ($request->route()->named('articles.by_section')) {
            $articles = $this->repository->bySection($slug);
        } else {
            $articles = Article::published()->search($slug)->get();
        }
        abort_if($articles->isEmpty(), 404, "Aucun contenu disponible");
        return view('site.article_listing', [
            'articles' => $articles,
        ]);
    }
}
