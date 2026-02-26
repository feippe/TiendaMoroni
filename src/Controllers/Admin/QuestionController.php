<?php
declare(strict_types=1);

namespace TiendaMoroni\Controllers\Admin;

use TiendaMoroni\Core\Session;
use TiendaMoroni\Models\QuestionModel;

class QuestionController
{
    public function index(array $params = []): void
    {
        Session::requireAdmin();

        $questions = QuestionModel::all(100);

        view('admin/questions/index', [
            'questions' => $questions,
            'pageTitle' => 'Preguntas – Admin',
        ]);
    }

    public function answer(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $id     = (int) ($params['id'] ?? 0);
        $answer = sanitize(post('answer', ''));

        if ($answer) {
            QuestionModel::answer($id, $answer);
        }

        redirect('/admin/preguntas');
    }

    public function delete(array $params = []): void
    {
        Session::requireAdmin();
        verifyCsrf();

        $id = (int) ($params['id'] ?? 0);
        QuestionModel::delete($id);
        redirect('/admin/preguntas');
    }
}
