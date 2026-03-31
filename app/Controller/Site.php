<?php
namespace Controller;

use Src\View;
use Model\User;
use Model\Book;
use Model\Reader;
use Model\Issuance;
use Src\Auth\Auth;
use Src\Validator;
use Illuminate\Support\Facades\App;

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
        return $view->render('site.dashboard', ['stats' => $stats]);
    }

    public function login(Request $request): string
    {
        if ($request->method === 'POST') {
            $validator = new Validator($request->all(), [
                'login' => ['required'],
                'password' => ['required']
            ], [
                "required" => "Поле :field обязательно для заполнения"
            ]);

            if ($validator->failed()) {
                return new View('site.login', [
                    'message' => json_encode($validator->error(), JSON_UNESCAPED_UNICODE)
                ]);
            }

            if (Auth:: attempt($request->all())) {
                app()->router()->redirect('/dashboard');
            }

            return new View('site.login', ['message' => 'неправильные логин или пароль']);
        }

        return new View('site.login');
    }

    public function signup(Request $request): string
    {
        if ($request->method === 'POST' && User::create($request->all())) {
            app()->route->redirect('/go');
        }
        return new View('site.signup');
    }

    public function logout(): void
    {
        Auth:: logout();
        app()->router()->redirect('/login');
    }

}