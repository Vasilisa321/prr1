<?php
namespace Controller;

use Src\View;
use Src\Request;
use Model\Book;
use Src\Validator\Validator;

class BookController
{
    public function index(Request $request): string
    {
        $query = Book::query();
        
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }
        
        $books = $query->orderBy('id', 'desc')->paginate(15);
        
        return (new View())->render('books.index', [
            'books' => $books,
            'search' => $search
        ]);
    }
    
    public function create(): string
    {
        return (new View())->render('books.create');
    }
    
    public function store(Request $request): void
    {
        $validator = new Validator($request->all(), [
            'title' => ['required'],
            'author' => ['required'],
            'year' => ['required'],
            'price' => ['required'],
            'total_copies' => ['required']
        ], [
            'required' => 'Поле :field обязательно для заполнения'
        ]);
        
        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->errors();
            app()->route->redirect('/books/create');
            return;
        }
        
        $data = $request->all();
        $data['available_copies'] = $data['total_copies'];
        $data['is_new_edition'] = isset($data['is_new_edition']) ? 1 : 0;
        
        Book::create($data);
        
        $_SESSION['success'] = 'Книга успешно добавлена';
        app()->route->redirect('/books');
    }
    
    public function edit(Request $request, int $id): string
    {
        $book = Book::find($id);
        
        if (!$book) {
            app()->route->redirect('/books');
        }
        
        return (new View())->render('books.edit', ['book' => $book]);
    }
    
    public function update(Request $request, int $id): void
    {
        $book = Book::find($id);
        
        if (!$book) {
            app()->route->redirect('/books');
        }
        
        $validator = new Validator($request->all(), [
            'title' => ['required'],
            'author' => ['required'],
            'year' => ['required'],
            'price' => ['required'],
            'total_copies' => ['required']
        ], [
            'required' => 'Поле :field обязательно для заполнения'
        ]);
        
        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->errors();
            app()->route->redirect("/books/edit/{$id}");
            return;
        }
        
        $data = $request->all();
        $oldCopies = $book->total_copies;
        $newCopies = $data['total_copies'];
        $data['available_copies'] = $book->available_copies + ($newCopies - $oldCopies);
        $data['is_new_edition'] = isset($data['is_new_edition']) ? 1 : 0;
        
        $book->update($data);
        
        $_SESSION['success'] = 'Книга успешно обновлена';
        app()->route->redirect('/books');
    }
    
    public function delete(int $id): void
    {
        $book = Book::find($id);
        
        if ($book && $book->available_copies == $book->total_copies) {
            $book->delete();
            $_SESSION['success'] = 'Книга удалена';
        } else {
            $_SESSION['errors'] = ['Нельзя удалить книгу, которая выдана читателям'];
        }
        
        app()->route->redirect('/books');
    }
}