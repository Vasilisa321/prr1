<?php
namespace Controller;

use Src\View;
use Src\Request;
use Model\Reader;
use Src\Validator\Validator;

class ReaderController
{
    public function index(Request $request): string
    {
        $query = Reader::query();
        
        if ($search = $request->get('search')) {
            $query->where('full_name', 'like', "%{$search}%")
                  ->orWhere('card_number', 'like', "%{$search}%");
        }
        
        $readers = $query->orderBy('id', 'desc')->paginate(15);
        
        return (new View())->render('readers.index', [
            'readers' => $readers,
            'search' => $search
        ]);
    }
    
    public function create(): string
    {
        return (new View())->render('readers.create');
    }
    
    public function store(Request $request): void
    {
        $validator = new Validator($request->all(), [
            'full_name' => ['required'],
            'address' => ['required'],
            'phone' => ['required']
        ], [
            'required' => 'Поле :field обязательно для заполнения'
        ]);
        
        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->errors();
            app()->route->redirect('/readers/create');
            return;
        }
        
        $data = $request->all();
        $data['card_number'] = 'LIB' . str_pad(Reader::count() + 1, 6, '0', STR_PAD_LEFT);
        $data['created_at'] = date('Y-m-d H:i:s');
        
        Reader::create($data);
        
        $_SESSION['success'] = 'Читатель успешно добавлен';
        app()->route->redirect('/readers');
    }
    
    public function edit(Request $request, int $id): string
    {
        $reader = Reader::find($id);
        
        if (!$reader) {
            app()->route->redirect('/readers');
        }
        
        return (new View())->render('readers.edit', ['reader' => $reader]);
    }
    
    public function update(Request $request, int $id): void
    {
        $reader = Reader::find($id);
        
        if (!$reader) {
            app()->route->redirect('/readers');
        }
        
        $validator = new Validator($request->all(), [
            'full_name' => ['required'],
            'address' => ['required'],
            'phone' => ['required']
        ], [
            'required' => 'Поле :field обязательно для заполнения'
        ]);
        
        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->errors();
            app()->route->redirect("/readers/edit/{$id}");
            return;
        }
        
        $reader->update($request->all());
        
        $_SESSION['success'] = 'Данные читателя обновлены';
        app()->route->redirect('/readers');
    }
    
    public function delete(int $id): void
    {
        $reader = Reader::find($id);
        
        if ($reader && $reader->activeIssuances()->count() == 0) {
            $reader->delete();
            $_SESSION['success'] = 'Читатель удален';
        } else {
            $_SESSION['errors'] = ['Нельзя удалить читателя, у которого есть книги на руках'];
        }
        
        app()->route->redirect('/readers');
    }
    
    public function show(int $id): string
    {
        $reader = Reader::with(['issuances' => function($q) {
            $q->with('book')->orderBy('issue_date', 'desc');
        }])->find($id);
        
        if (!$reader) {
            app()->route->redirect('/readers');
        }
        
        return (new View())->render('readers.show', ['reader' => $reader]);
    }
}