<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Response;
use \setasign\Fpdi\Fpdi;
use Illuminate\Http\Request;
use Imagick;
use Orbitale\Component\ImageMagick\Command;
use Exception;

class FormatController extends Controller
{

  public function __construct() {}

  public function get_files() {
    try {
    $c = $this->getDirContents(public_path() . '/files');

    $path = $c[0];
    while(is_dir($path)) {
      rmdir($path);
      $c = $this->getDirContents(public_path() . '/files');
      $path = $c[0];
    }
    $pdf = new Fpdi;
    $pages = $pdf->setSourceFile($path);


    $images = [];
    $page_path = $path;

    $com = new Command('/usr/local/bin');
    $im = $com->convert($page_path)->file('page-%d.jpg', false)->run();
    $output = $im;
    //$images[] = base64_encode($im);
    for ($i = 0; $i < $pages; $i++) {
      $img_path = public_path('page-'.$i.'.jpg');
      $data = file_get_contents($img_path);
      $base64 = 'data:image/jpg;base64,' . base64_encode($data);
      $images[] = $base64;
      unlink($img_path);
    }

    return view('format', ["data" => [
      "images" => json_encode($images),
      "year" => $this->extract_year(basename($path)),
      "title" => $this->extract_title(basename($path)),
      "debug" => $path,
      "path" => $path
      ]]);
    } catch (Exception $e) {
      return view('format', ["data" => [
        "images" => json_encode([]),
        "year" => '',
        "title" => '',
        "debug" => $path,
        "path" => $path
      ]]);
    }
  }

  private function extract_title($str) {
    $ret = $str;
    $ret = str_replace('_', ' ', $ret); // cambia _ por espacio
    $ret = str_replace('.pdf', '', $ret);
    $ret = str_replace('CBC', '', $ret);
    $ret = str_replace('(UBA)', '', $ret);
    $ret = str_replace('UBA', '', $ret);

    $ret = str_replace('Ciencias Políticas', '', $ret);
    $ret = str_replace('Conocimiento Proyectual 1', '', $ret);
    $ret = str_replace('Conocimiento Proyectual 2', '', $ret);
    $ret = str_replace('Derecho', '', $ret);
    $ret = str_replace('Derechos Humanos y Derecho Constitucional', '', $ret);
    $ret = str_replace('Dibujo', '', $ret);
    $ret = str_replace('Economía', '', $ret);
    $ret = str_replace('Folosofía', '', $ret);
    $ret = str_replace('Física', '', $ret);
    $ret = str_replace('Física', '', $ret);
    $ret = str_replace('Matemática', '', $ret);
    $ret = str_replace('Matemática (Agronomía)', '', $ret);
    $ret = str_replace('Matemática Agronomía', '', $ret);
    $ret = str_replace('Pensamiento Científico', '', $ret);
    $ret = str_replace('Psicología', '', $ret);
    $ret = str_replace('Química', '', $ret);
    $ret = str_replace('Química (Agronomía)', '', $ret);
    $ret = str_replace('Química (Agronomía)', '', $ret);
    $ret = str_replace('Semiología', '', $ret);
    $ret = str_replace('Sociologia', '', $ret);
    $ret = str_replace('Taller de Semiología', '', $ret);


    $ret = str_replace('...', '', $ret);
    $ret = str_replace('Universidad de Buenos Aires', '', $ret);
    $ret = str_replace(' )', ')', $ret); // cambia los " )" por ")"
    $ret = preg_replace("/(.)$/", "", $ret); // borra ultimo caracter agregado por el descargador automatico (espacio - caracter)
    $ret = preg_replace("/\d{4}/", "", $ret); // borra el año (cuatro digitos)
    $ret = str_replace('()', '', $ret);
    $ret = preg_replace('/\s+/', ' ', $ret);
    $ret = str_replace('- -', '', $ret);
    return $ret;
  }

  private function extract_year($str) {
    $ret = '';
    if (preg_match('/\d{4}/', $str, $matches)) {
      $ret = $matches[0];
    }
    return $ret;
  }

  private function getDirContents($dir, &$results = array()){
      $files = scandir($dir);

      foreach($files as $key => $value){
          $path = realpath($dir.'/'.$value);
          if(!is_dir($path)) {
              $results[] = $path;
          } else if($value != "." && $value != "..") {
              $this->getDirContents($path, $results);
              $results[] = $path;
          }
      }

      return $results;
  }


  public function save_pdf(Request $request) {
    $pdf = new Fpdi();
    $pages = $pdf->setSourceFile($request->path);
    for ($page = 0; $page < $pages; $page++) {
      $pageId = $pdf->importPage($page+1);
      if (!in_array($page, $request->deleted)) {
        $pdf->AddPage();
        $pdf->useTemplate($pageId,0,0);
        $size = $pdf->getTemplateSize($pageId);
        $width = $size['width'];
        $height = $size['height'];
        foreach($request->areas[$page] as $area) {
          $x = ($area['x'] * $width) / 100;
          $y = ($area['y'] * $height) / 100;
          $white_width = ($area['width'] * $width) / 100;
          $white_height = ($area['height'] * $height) / 100;
          $pdf->Image(public_path('white.png'), $x, $y, $white_width, $white_height);
        }
      }
    }
    $path = explode('/files/', $request->path)[1];
    $output_path = public_path() . '/formatted/' . dirname($path) . '/' . trim($request->title) . ' ' . $request->year . '.pdf';
    if (!file_exists(dirname($output_path)))
      mkdir(dirname($output_path), 0777, true);

    $exists = true;
    $try = 1;
    while($exists) {
      if (file_exists($output_path))
        $output_path = public_path() . '/formatted/' . dirname($path) . '/' . trim($request->title) . ' ' . $request->year . ' ' . $try . '.pdf';
      else
        $exists = false;
      $try++;
    }
    $pdf->Output('f', $output_path);
    // unlink($request->path);
    return Response::json(true, 200);
  }



}
