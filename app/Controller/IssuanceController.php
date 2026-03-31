<?php
namespace Controller;

use Src\View;
use Src\Request;
use Model\Book;
use Model\Reader;
use Model\Issuance;
use Src\Validator\Validator;

class IssuanceController
{
    public function index(Request $request): string
    {
        $query = Issuance::with(['reader', 'book']);
        
        if ($type = $request->get('type')) {
            if ($type === 'active') {
                $query->whereNull('return_date');
            } elseif ($type === 'overdue') {
                $query->whereNull('return_date')
                      ->where('due_date', '<', date('Y-m-d'));
            } elseif ($type === 'returned') {
                $query->whereNotNull('return_date');
            }
        }
        
        $issuances = $query->orderBy('issue_date', 'desc')->paginate(20);
        
        return (new View())->render('issuances.index', [
            'issuances' => $issuances,
            'type' => $type
        ]);
    }
    
    public function create(): string
    {
        $readers = Reader::orderBy('full_name')->get();
        $books = Book::where('available_copies', '>', 0)->orderBy('title')->get();
        
        return (new View())->render('issuances.create', [
            'readers' => $readers,
            'books' => $books
        ]);
    }
    
    public function store(Request $request): void
    {
        $validator = new Validator($request->all(), [
            'reader_id' => ['required'],
            'book_id' => ['required'],
            'issue_date' => ['required']
        ], [
            'required' => 'Поле :field обязательно для заполнения'
        ]);
        
        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->errors();
            app()->route->redirect('/issuances/create');
            return;
        }
        
        $book = Book::find($request->get('book_id'));
        
        if (!$book->isAvailable()) {
            $_SESSION['errors'] = ['Эта книга недоступна для выдачи'];
            app()->route->redirect('/issuances/create');
            return;
        }
        
        $dueDate = date('Y-m-d', strtotime($request->get('issue_date') . ' + 14 days'));
        
        Issuance::create([
            'reader_id' => $request->get('reader_id'),
            'book_id' => $request->get('book_id'),
            'issue_date' => $request->get('issue_date'),
            'due_date' => $request->get('due_date') ?? $dueDate
        ]);
        
        $book->decrementCopies();
        
        $_SESSION['success'] = 'Книга успешно выдана';
        app()->route->redirect('/issuances');
    }
    
    public function return(int $id): void
    {
        $issuance = Issuance::find($id);
        
        if ($issuance && is_null($issuance->return_date)) {
            $issuance->update(['return_date' => date('Y-m-d')]);
            $issuance->book->incrementCopies();
            $_SESSION['success'] = 'Книга возвращена';
        } else {
            $_SESSION['errors'] = ['Ошибка при возврате книги'];
        }
        
        app()->route->redirect('/issuances');
    }
    
    public function report(Request $request): string
    {
        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));
        
        $popularBooks = Issuance::selectRaw('book_id, COUNT(*) as count')
            ->with('book')
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->groupBy('book_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        return (new View())->render('issuances.report', [
            'popularBooks' => $popularBooks,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
    
    public function readerBooks(int $id): string
    {
        $reader = Reader::with(['activeIssuances.book'])->find($id);
        
        if (!$reader) {
            app()->route->redirect('/readers');
        }
        
        return (new View())->render('issuances.reader_books', ['reader' => $reader]);
    }
    
    public function bookReaders(int $id): string
    {
        $book = Book::with(['issuances' => function($q) {
            $q->with('reader')->orderBy('issue_date', 'desc');
        }])->find($id);
        
        if (!$book) {
            app()->route->redirect('/books');
        }
        
        return (new View())->render('issuances.book_readers', ['book' => $book]);
    }
}