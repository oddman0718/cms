<?php
// src/Controller/ArticlesController.php

namespace App\Controller;

use App\Controller\AppController;

class ArticlesController extends AppController
{
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('Paginator');
        $this->loadComponent('Flash'); // FlashComponent をインクルード
        $this->Auth->allow(['tags']);
    }

    public function index()
    {
        $this->loadComponent('Paginator');
        $articles = $this->Paginator->paginate($this->Articles->find());
        $this->set(compact('articles'));
    }

    public function view($slug = null)
    {
    $article = $this->Articles->findBySlug($slug)->firstOrFail();
    $this->set(compact('article'));
    }

    public function add()
    {
        $article = $this->Articles->newEntity();
        if ($this->request->is('post')) {
            $article = $this->Articles->patchEntity($article, $this->request->getData());

            // user_id の決め打ちは一時的なもので、あとで認証を構築する際に削除されます。
            $article->user_id = $this->Auth->user('id');

            if ($this->Articles->save($article)) {
                $this->Flash->success(__('Your article has been saved.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('Unable to add your article.'));
        }
        // タグのリストを取得
        $tags = $this->Articles->Tags->find('list');

        // ビューコンテキストに tags をセット
        $this->set('tags', $tags);

        $this->set('article', $article);
    }

    public function edit($slug)
    {
        $article = $this->Article
        ->findBySlug($slug)
        ->contain('Tags') // 関連づけられた Tags を読み込む
        ->firstOrFail();
        if ($this->request->is(['post', 'put'])) {
            $this->Articles->patchEntity($article, $this->request->getData(),[
                'accessibleFields' => ['user_id' => false]
            ]);
            if ($this->Articles->save($article)) {
                $this->Flash->success(__('Your article has been updated.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('Unable to update your article.'));
        }
    // タグのリストを取得
    $tags = $this->Articles->Tags->find('list');

    // ビューコンテキストに tags をセット
    $this->set('tags', $tags);

    $this->set('article', $article);
    }

    public function delete($slug)
    {
        $this->request->allowMethod(['post', 'delete']);

        $article = $this->Articles->findBySlug($slug)->firstOrFail();
        if ($this->Articles->delete($article)) {
            $this->Flash->success(__('The {0} article has been deleted.', $article->title));
            return $this->redirect(['action' => 'index']);
        }
    }

    public function tags()
    {
        // 'pass' キーは CakePHP によって提供され、リクエストに渡された
        // 全ての URL パスセグメントを含みます。
        $tags = $this->request->getParam('pass');

        // ArticlesTable を使用してタグ付きの記事を検索します。
        $articles = $this->Articles->find('tagged', [
            'tags' => $tags
        ]);

        // 変数をビューテンプレートのコンテキストに渡します。
        $this->set([
            'articles' => $articles,
            'tags' => $tags
        ]);
    }

    public function isAuthorized($user)
    {
        $action = $this->request->getParam('action');
        // add および tags アクションは、常にログインしているユーザーに許可されます。
        if (in_array($action, ['add', 'tags'])) {
            return true;
        }

        // 他のすべてのアクションにはスラッグが必要です。
        $slug = $this->request->getParam('pass.0');
        if (!$slug) {
            return false;
        }

        // 記事が現在のユーザーに属していることを確認します。
        $article = $this->Articles->findBySlug($slug)->first();

        return $article->user_id === $user['id'];
    }

}