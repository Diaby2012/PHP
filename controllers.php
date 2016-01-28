<?php

use Gregwar\Image\Image;

$app->match('/', function() use ($app) {
    return $app['twig']->render('home.html.twig');
})->bind('home');

$app->match('/books', function() use ($app) {
    return $app['twig']->render('books.html.twig', array(
        'books' => $app['model']->getBooks()
    ));
})->bind('books');

//la page fiche livre
$app->match('/books/{id}', function($id) use ($app){
    return $app['twig']->render('book.html.twig', array(
        'book' => $app['model']->getBook($id),
        'NbreExemplaires' => $app['model']->getNbreExemplaires($id),
        'exemplaires' => $app['model']->getExemplaires('$id'),
        'emprunts' => $app['model']->getExemplaires($id)
    ));
})->bind('book');

/*
* Page empunt
*/
$app->match('/emprunt/{id}', function($id) use ($app){
    $request = $app['request'];
    if ($request->getMethod() == 'POST'){
        $post = $request->request;
        if ($post->has('nom') && $post->has('dateFin')) {
            $now = date('d-m-Y');
            $dateFin = $post->get('dateFin');
            $now = new DateTime($now);
            $now = $now->format('dmY');
            $dateFin = new DateTime($dateFin);
            $dateFin = $dateFin->format('dmY');
            if ($dateFin > $now) {
                $app['model']->setEmprunt($post->get('nom'),$post->get('dateFin'), $id);
            }
        }
    }
    return $app['twig']->render('emprunt.html.twig');
})->bind('emprunt');

$app->match('/admin', function() use ($app) {
    $request = $app['request'];
    $success = false;
    if ($request->getMethod() == 'POST') {
        $post = $request->request;
        if ($post->has('login') && $post->has('password') &&
            array($post->get('login'), $post->get('password')) == $app['config']['admin']) {
            $app['session']->set('admin', true);
            $success = true;
        }
    }
    return $app['twig']->render('admin.html.twig', array(
        'success' => $success
    ));
})->bind('admin');

$app->match('/logout', function() use ($app) {
    $app['session']->remove('admin');
    return $app->redirect($app['url_generator']->generate('admin'));
})->bind('logout');

$app->match('/addBook', function() use ($app) {
    if (!$app['session']->has('admin')) {
        return $app['twig']->render('shouldBeAdmin.html.twig');
    }

    $request = $app['request'];
    if ($request->getMethod() == 'POST') {
        $post = $request->request;
        if ($post->has('title') && $post->has('author') && $post->has('synopsis') &&
            $post->has('copies')) {
            $files = $request->files;
            $image = '';

            // Resizing image
            if ($files->has('image') && $files->get('image')) {
                $image = sha1(mt_rand().time());
                Image::open($files->get('image')->getPathName())
                    ->resize(240, 300)
                    ->save('uploads/'.$image.'.jpg');
                Image::open($files->get('image')->getPathName())
                    ->resize(120, 150)
                    ->save('uploads/'.$image.'_small.jpg');
            }

            // Saving the book to database
            $app['model']->insertBook($post->get('title'), $post->get('author'), $post->get('synopsis'),
                $image, (int)$post->get('copies'));
        }
    }

    return $app['twig']->render('addBook.html.twig');
})->bind('addBook');

