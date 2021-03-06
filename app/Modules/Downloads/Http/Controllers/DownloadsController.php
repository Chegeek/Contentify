<?php

namespace App\Modules\Downloads\Http\Controllers;

use App\Modules\Downloads\Download;
use App\Modules\Downloads\DownloadCat;
use Config;
use Contentify\GlobalSearchInterface;
use File;
use FrontController;
use Response;
use URL;

class DownloadsController extends FrontController implements GlobalSearchInterface
{

    public function __construct()
    {
        $this->modelClass = Download::class;

        parent::__construct();
    }

    public function index()
    {
        $perPage = Config::get('app.frontItemsPerPage');

        // NOTE: Add has('downloads') to show only categories that have downloads
        $downloadCats = DownloadCat::paginate($perPage);

        $this->pageView('downloads::index', compact('downloadCats'));
    }

    /**
     * Show a category
     * 
     * @param  int $id The id of the category
     * @return void
     */
    public function showCategory($id)
    {
        /** @var DownloadCat $downloadCat */
        $downloadCat = DownloadCat::findOrFail($id);

        $downloadCat->access_counter++;
        $downloadCat->save();

        $perPage = Config::get('app.frontItemsPerPage');
        $downloads = Download::whereDownloadCatId($id)->paginate($perPage);

        $this->title($downloadCat->title);

        $this->pageView('downloads::category', compact('downloadCat', 'downloads'));
    }

    /**
     * Show a download detail page
     * 
     * @param  int $id The id of the download
     * @return void
     */
    public function show($id)
    {
        /** @var Download $download */
        $download = Download::findOrFail($id);

        $this->title($download->title);

        $this->pageView('downloads::show', compact('download'));
    }

    /**
     * Perform a download
     * 
     * @param  int $id The id of the download
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function perform($id)
    {
        /** @var Download $download */
        $download = Download::findOrFail($id);

        $download->access_counter++;
        $download->save();

        $extension = File::extension($download->file);
        $shortName = $download->slug;
        if ($extension) {
            $shortName .= '.'.$extension;
        }
        return Response::download($download->uploadPath(true).$download->file, $shortName);
    }
    
    /**
     * This method is called by the global search (SearchController->postCreate()).
     * Its purpose is to return an array with results for a specific search query.
     * 
     * @param  string $subject The search term
     * @return string[]
     */
    public function globalSearch($subject)
    {
        $downloads = Download::where('title', 'LIKE', '%'.$subject.'%')->get();

        $results = array();
        foreach ($downloads as $download) {
            $results[$download->title] = URL::to('downloads/'.$download->id.'/'.$download->slug);
        }

        return $results;
    }

}