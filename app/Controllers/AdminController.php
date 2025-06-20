<?php

namespace App\Controllers;

use App\Models\QuoteModel;
use App\Models\ArticleModel;
use App\Models\UserModel;
use CodeIgniter\Controller;

class AdminController extends BaseController
{
    protected $quoteModel;
    protected $articleModel;
    protected $userModel;

    public function __construct()
    {
        $this->quoteModel = new QuoteModel();
        $this->articleModel = new ArticleModel();
        $this->userModel = new UserModel();
    }

    // Dashboard Admin
    public function index()
    {
        $totalQuotes = $this->quoteModel->countAllResults();
        $totalArticles = $this->articleModel->countAllResults();
        $totalUsers = $this->userModel->countAllResults();

        $data = [
            'pageTitle'     => 'Dashboard Admin ChillMed',
            'totalQuotes'   => $totalQuotes,
            'totalArticles' => $totalArticles,
            'totalUsers'    => $totalUsers,
        ];
        return view('admin/admin', $data); // halaman admin
    }

    // quotes controller
    public function quotes()
    {
        $data = [
            'pageTitle' => 'Manajemen Quotes',
            'quotes'    => $this->quoteModel->findAll(),
        ];
        return view('admin/manage_quotes', $data);
    }

    // tammbah quote
    public function addQuote()
    {
        if ($this->request->getMethod() === 'POST') {
            $validation = \Config\Services::validation();
            $rules = [
                'quote_text' => 'required|min_length[5]|max_length[500]',
            ];
            $validation->setRules($rules);

            if (!$validation->withRequest($this->request)->run()) {
                return redirect()->back()->withInput()->with('errors', $validation->getErrors());
            }

            // user id dan author
            $loggedInUserId = session()->get('user')['id'];
            $loggedInUserName = session()->get('user')['name'];

            $data = [
                'user_id'    => $loggedInUserId,
                'quote_text' => $this->request->getPost('quote_text'),
                'author'     => $loggedInUserName, // nama user dari yang upload
            ];

            if ($this->quoteModel->insert($data)) {
                return redirect()->to(base_url('admin/quotes'))->with('success', 'Quote berhasil ditambahkan!');
            } else {
                return redirect()->back()->withInput()->with('error', 'Gagal menambahkan quote.');
            }
        }
        return view('admin/quotes_add', ['pageTitle' => 'Tambah Quote Baru']);
    }
    // edit quote
    public function editQuote($id)
    {
        $quote = $this->quoteModel->find($id);

        if (!$quote) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($this->request->getMethod() === 'POST') {
            $validation = \Config\Services::validation();
            $rules = [
                'quote_text' => 'required|min_length[5]|max_length[500]',
            ];
            $validation->setRules($rules);

            if (!$validation->withRequest($this->request)->run()) {
                return redirect()->back()->withInput()->with('errors', $validation->getErrors());
            }
            
            $loggedInUserId = session()->get('user')['id'];
            $loggedInUserName = session()->get('user')['name'];

            $data = [
                'user_id'    => $loggedInUserId, 
                'quote_text' => $this->request->getPost('quote_text'),
                'author'     => $loggedInUserName,
            ];

            if ($this->quoteModel->update($id, $data)) {
                return redirect()->to(base_url('admin/quotes'))->with('success', 'Quote berhasil diperbarui!');
            } else {
                return redirect()->back()->withInput()->with('error', 'Gagal memperbarui quote.');
            }
        }
        return view('admin/quotes_edit', ['pageTitle' => 'Edit Quote', 'quote' => $quote]);
    }

    // delete quote
    public function deleteQuote($id)
    {
        if ($this->request->getMethod() === 'POST') {
            if ($this->quoteModel->delete($id)) {
                return redirect()->to(base_url('admin/quotes'))->with('success', 'Quote berhasil dihapus!');
            } else {
                return redirect()->to(base_url('admin/quotes'))->with('error', 'Gagal menghapus quote.');
            }
        }
        return redirect()->to(base_url('admin/quotes'))->with('error', 'Metode penghapusan tidak diizinkan.');
    }


    // artikel contoller
    public function articles()
    {
        $data = [
            'pageTitle' => 'Manajemen Artikel',
            'articles'  => $this->articleModel->findAll(),
        ];
        return view('admin/manage_articles', $data);
    }

    public function addArticle()
    {
        if ($this->request->getMethod() === 'POST') {
            $validation = \Config\Services::validation();
            $rules = [
                'title'   => 'required|min_length[5]|max_length[255]',
                'content' => 'required|min_length[20]',
                'image'   => 'uploaded[image]|max_size[image,1024]|is_image[image]',
            ];
            $validation->setRules($rules);

            if (!$validation->withRequest($this->request)->run()) {
                return redirect()->back()->withInput()->with('errors', $validation->getErrors());
            }

            $loggedInUserId = session()->get('user')['id'];
            $loggedInUserName = session()->get('user')['name'];

            $file = $this->request->getFile('image');
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'images', $newName);

            $data = [
                'user_id' => $loggedInUserId,
                'title'   => $this->request->getPost('title'),
                'content' => $this->request->getPost('content'),
                'author'  => $loggedInUserName,
                'image'   => $newName,
            ];

            if ($this->articleModel->insert($data)) {
                return redirect()->to(base_url('admin/articles'))->with('success', 'Artikel berhasil ditambahkan!');
            } else {
                return redirect()->back()->withInput()->with('error', 'Gagal menambahkan artikel.');
            }
        }
        return view('admin/add_article_form', ['pageTitle' => 'Tambah Artikel Baru']);
    }

    // edit article
    public function editArticle($id)
    {
        $article = $this->articleModel->find($id);

        if (!$article) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($this->request->getMethod() === 'POST') {
            $validation = \Config\Services::validation();
            $rules = [
                'title'   => 'required|min_length[5]|max_length[255]',
                'content' => 'required|min_length[20]',
            ];
            if ($this->request->getFile('image')->isValid() && ! $this->request->getFile('image')->hasMoved()) {
                $rules['image'] = 'uploaded[image]|max_size[image,1024]|is_image[image]';
            }
            $validation->setRules($rules);

            if (!$validation->withRequest($this->request)->run()) {
                return redirect()->back()->withInput()->with('errors', $validation->getErrors());
            }

            $loggedInUserId = session()->get('user')['id'];
            $loggedInUserName = session()->get('user')['name'];
            
            $data = [
                'user_id' => $loggedInUserId,
                'title'   => $this->request->getPost('title'),
                'content' => $this->request->getPost('content'),
                'author'  => $loggedInUserName,
            ];

            $file = $this->request->getFile('image');
            if ($file->isValid() && ! $file->hasMoved()) {
                if ($article['image'] && file_exists(FCPATH . 'images/' . $article['image'])) {
                    unlink(FCPATH . 'images/' . $article['image']);
                }
                $newName = $file->getRandomName();
                $file->move(FCPATH . 'images', $newName);
                $data['image'] = $newName;
            }

            if ($this->articleModel->update($id, $data)) {
                return redirect()->to(base_url('admin/articles'))->with('success', 'Artikel berhasil diperbarui!');
            } else {
                return redirect()->back()->withInput()->with('error', 'Gagal memperbarui artikel.');
            }
        }
        return view('admin/edit_article_form', ['pageTitle' => 'Edit Artikel', 'article' => $article]);
    }

    public function deleteArticle($id)
    {
        if ($this->request->getMethod() === 'POST') {
            $article = $this->articleModel->find($id);
            if ($article) {
                if ($article['image'] && file_exists(FCPATH . 'images/' . $article['image'])) {
                    unlink(FCPATH . 'images/' . $article['image']);
                }

                if ($this->articleModel->delete($id)) {
                    return redirect()->to(base_url('admin/articles'))->with('success', 'Artikel berhasil dihapus!');
                } else {
                    return redirect()->to(base_url('admin/articles'))->with('error', 'Gagal menghapus artikel.');
                }
            } else {
                return redirect()->to(base_url('admin/articles'))->with('error', 'Artikel tidak ditemukan.');
            }
        }
        return redirect()->to(base_url('admin/articles'))->with('error', 'Metode penghapusan tidak diizinkan.');
    }

    // user controller
    public function users()
    {
        $data = [
            'pageTitle' => 'Manajemen Users',
            'users'     => $this->userModel->findAll(),
        ];
        return view('admin/manage_users', $data);
    }

    // edit user role
    public function editUserRole($id)
    {
        if ($this->request->isAJAX() && $this->request->getMethod() === 'POST') {
            $user = $this->userModel->find($id);

            if (!$user) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'User tidak ditemukan.']);
            }

            $newRole = $this->request->getPost('role');

            if (!in_array($newRole, ['admin', 'user'])) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Role tidak valid.']);
            }

            if (session()->get('user')['id'] == $id && $newRole !== 'admin') {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Anda tidak bisa mengubah role Anda sendiri.']);
            }

            if ($user['role'] === $newRole) {
                return $this->response->setJSON(['status' => 'success', 'message' => 'Role user tidak berubah.']);
            }

            if ($this->userModel->update($id, ['role' => $newRole])) {
                return $this->response->setJSON(['status' => 'success', 'message' => 'Role user berhasil diperbarui!']);
            } else {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal memperbarui role user.']);
            }
        }
        return $this->response->setStatusCode(405)->setJSON(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
    }

    // delete user
    public function deleteUser($id)
    {
        if ($this->request->getMethod() === 'POST') {
            if (session()->get('user')['id'] == $id) {
                return redirect()->to(base_url('admin/users'))->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
            }

            if ($this->userModel->delete($id)) {
                return redirect()->to(base_url('admin/users'))->with('success', 'User berhasil dihapus!');
            } else {
                return redirect()->to(base_url('admin/users'))->with('error', 'Gagal menghapus user.');
            }
        }
        return redirect()->to(base_url('admin/users'))->with('error', 'Metode penghapusan tidak diizinkan.');
    }
}
