<?php
namespace Controller;

use Src\View;
use Model\User;
use Model\Book;
use Model\Reader;
use Model\Issuance;

class Site
{
    public function index(): string
    {
        $stats = [
            'total_books' => Book::count(),
            'total_readers' => Reader::count(),
            'active_loans' => Issuance::whereNull('return_date')->count(),
            'overdue_loans' => Issuance::whereNull('return_date')
                ->where('return_date', '<', date('Y-m-d'))
                ->count(),
            'total_issuances' => Issuance:: with(['reader', 'book'])
            ->orderBy('issue_date', 'DESC')
            ->limit(5)
            ->get()
        ];

        $view = new View();
        return $view->render('site.hello', ['message' => 'index working']);
    }

    public function hello(): string
    {
        return new View('site.hello', ['message' => 'hello working']);
    }
}