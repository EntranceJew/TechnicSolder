<?php

use Illuminate\Support\MessageBag;
use League\Csv\Reader;

class ModController extends BaseController {

	public function __construct()
	{
		parent::__construct();
		$this->beforeFilter('perm', array('solder_mods'));
		$this->beforeFilter('perm', array('mods_manage'), array('only' => array('view','versions')));
		$this->beforeFilter('perm', array('mods_create'), array('only' => array('create', 'bulk')));
		$this->beforeFilter('perm', array('mods_delete'), array('only' => array('delete')));
	}

		public function getIndex()
	{
		return Redirect::to('mod/list');
	}

	public function getList()
	{
		$mods = Mod::all();
		return View::make('mod.list')->with(array('mods' => $mods));
	}

	public function getView($mod_id = null)
	{
		$mod = Mod::find($mod_id);
		if (empty($mod))
			return Redirect::to('mod/list')->withErrors(new MessageBag(array('Mod not found')));

		return View::make('mod.view')->with(array('mod' => $mod));
	}

	public function getCreate()
	{
		return View::make('mod.create');
	}

	public function postCreate()
	{
		$rules = array(
			'name' => 'required|unique:mods',
			'pretty_name' => 'required',
			'link' => 'url',
			'donatelink' => 'url',
			);
		$messages = array(
			'name.required' => 'You must fill in a mod slug name.',
			'name.unique' => 'The slug you entered is already taken',
			'pretty_name.required' => 'You must enter in a mod name',
			'link.url' => 'You must enter a properly formatted Website',
			'donatelink.url' => 'You must enter a proper formatted Donation Link',
			);

		$validation = Validator::make(Input::all(), $rules, $messages);
		if ($validation->fails())
			return Redirect::to('mod/create')->withErrors($validation->messages());

		$mod = new Mod();
		$mod->name = Str::slug(Input::get('name'));
		$mod->pretty_name = Input::get('pretty_name');
		$mod->author = Input::get('author');
		$mod->description = Input::get('description');
		$mod->link = Input::get('link');
		$mod->donatelink = Input::get('donatelink');
		$mod->save();
		return Redirect::to('mod/view/'.$mod->id);
	}

	public function getBulk()
	{
		return View::make('mod.bulk');
	}

	public function postBulk()
	{
		$rules = array(
			'csv' => 'required|csv',
			);
		$messages = array(
			'csv.required' => 'You must select a file to upload.',
			'csv.csv' => 'The file either did not have the csv extension or did not read like a csv.'
			);

		$file_validation = Validator::make(Input::all(), $rules, $messages);
		if ($file_validation->fails())
			return Redirect::to('mod/bulk')->withErrors($file_validation->messages());

		$file = Input::file('csv');
		$csv = Reader::createFromPath($file->getRealPath());
		$data = $csv->fetchAssoc(0, function ($row){
			return array_map('trim', $row);
		});
		$errors = array();
		$notices = array();
		foreach ($data as $line => $mod_data)
		{
			$rules = array(
				'name' => 'required|unique:mods',
				'pretty_name' => 'required',
				'link' => 'url',
				'donatelink' => 'url',
				);
			$messages = array(
				'name.required' => 'On line: ' . (intval($line) + 1) . ' you are missing a mod slug name.',
				'name.unique' => 'The slug on line: ' . (intval($line) + 1) . ' for mod: ' . $mod_data['name'] . ' is already taken',
				'pretty_name.required' => 'On line: ' . (intval($line) + 1) . ' for mod: ' . $mod_data['name'] . ' is missing a mod name',
				'link.url' => 'On line: ' . (intval($line) + 1) . ' for mod: ' . $mod_data['pretty_name'] . ' you must enter a properly formatted Website',
				'donatelink.url' => 'On line: ' . (intval($line) + 1) . ' for mod: ' . $mod_data['pretty_name'] . ' you must enter a proper formatted Donation Link',
				);

			$validation = Validator::make($mod_data, $rules, $messages);
			if ($validation->fails())
			{
				foreach ($validation->messages()->all() as $error)
				{
					$errors[] = $error;
				}
				continue;
			}

			$mod = new Mod();
			$mod->name = Str::slug($mod_data['name']);
			$mod->pretty_name = $mod_data['pretty_name'];
			$mod->author = $mod_data['author'];
			$mod->description = $mod_data['description'];
			$mod->link = $mod_data['link'];
			$mod->donatelink = $mod_data['donatelink'];
			$mod->save();
			$notices[] = 'Added ' . $mod_data['pretty_name'] . ' to Mod List.';
		}
		if (!empty($errors))
			return Redirect::to('mod/bulk')->withErrors($errors)->with('notices', $notices);

		return Redirect::to('mod/list')->with('notices', $notices);
	}

	public function getDelete($mod_id = null)
	{
		$mod = Mod::find($mod_id);
		if (empty($mod))
			return Redirect::to('mod/list')->withErrors(new MessageBag(array('Mod not found')));

		return View::make('mod.delete')->with(array('mod' => $mod));
	}

	public function postModify($mod_id = null)
	{
		$mod = Mod::find($mod_id);
		if (empty($mod))
			return Redirect::to('mod/list')->withErrors(new MessageBag(array('Error modifying mod - Mod not found')));

		$rules = array(
			'pretty_name' => 'required',
			'name' => 'required|unique:mods,name,'.$mod->id,
			'link' => 'url',
			'donatelink' => 'url',
			);

		$messages = array(
			'name.required' => 'You must fill in a mod slug name.',
			'name.unique' => 'The slug you entered is already in use by another mod',
			'pretty_name.required' => 'You must enter in a mod name',
			'link.url' => 'You must enter a properly formatted Website',
			'donatelink.url' => 'You must enter a proper formatted Donation Link',
			);

		$validation = Validator::make(Input::all(), $rules, $messages);
		if ($validation->fails())
			return Redirect::to('mod/view/'.$mod->id)->withErrors($validation->messages());

		$mod->pretty_name = Input::get('pretty_name');
		$mod->name = Input::get('name');
		$mod->author = Input::get('author');
		$mod->description = Input::get('description');
		$mod->link = Input::get('link');
		$mod->donatelink = Input::get('donatelink');
		$mod->save();
		Cache::forget('mod.'.$mod->name);

		return Redirect::to('mod/view/'.$mod->id)->with('success','Mod successfully edited.');
	}

	public function postDelete($mod_id = null)
	{
		$mod = Mod::find($mod_id);
		if (empty($mod))
			return Redirect::to('mod/list')->withErrors(new MessageBag(array('Error deleting mod - Mod not found')));

		foreach ($mod->versions as $ver)
		{
			$ver->builds()->sync(array());
			$ver->delete();
		}
		$mod->delete();
		Cache::forget('mod.'.$mod->name);

		return Redirect::to('mod/list')->with('success','Mod deleted!');
	}

	public function anyRehash()
	{
		if (Request::ajax())
		{
			$md5 = Input::get('md5');
			$ver_id = Input::get('version-id');
			if (empty($ver_id))
				return Response::json(array(
									'status' => 'error',
									'reason' => 'Missing Post Data',
									));

			$ver = Modversion::find($ver_id);
			if (empty($ver))
				return Response::json(array(
									'status' => 'error',
									'reason' => 'Could not pull mod version from database',
									));

			if (empty($md5)) {
				$file_md5 = $this->mod_md5($ver->mod,$ver->version);
				$md5 = $file_md5;
			} else {
				$file_md5 = $this->mod_md5($ver->mod,$ver->version);
				$pfile_md5 = empty($file_md5) ? "Null" : $file_md5;
			}

			if ($md5 == $file_md5 && !empty($md5)) {
				$ver->md5 = $md5;
				$ver->save();
				return Response::json(array(
							'status' => 'success',
							'version_id' => $ver->id,
							'md5' => $ver->md5,
							));
			} else if ($md5 != $file_md5 && !empty($md5)) {
				$ver->md5 = $md5;
				$ver->save();
				return Response::json(array(
							'status' => 'warning',
							'version_id' => $ver->id,
							'md5' => $ver->md5,
							'reason' => 'MD5 provided does not match file MD5: ' . $pfile_md5
							));
			} else {
				return Response::json(array(
							'status' => 'error',
							'reason' => 'Remote MD5 failed. See app/storage/logs for more details'
							));
			}
		}

		return Response::view('errors.missing', array(), 404);
	}

	public function anyAddVersion()
	{
		if (Request::ajax())
		{
			$mod_id = Input::get('mod-id');
			$md5 = Input::get('add-md5');
			$version = Input::get('add-version');
			if (empty($mod_id) || empty($version))
				return Response::json(array(
							'status' => 'error',
							'reason' => 'Missing Post Data'
							));

			$mod = Mod::find($mod_id);
			if (empty($mod))
				return Response::json(array(
							'status' => 'error',
							'reason' => 'Could not pull mod from database'
							));

			if (empty($md5)) {
				$file_md5 = $this->mod_md5($mod,$version);
				$md5 = $file_md5;
			} else {
				$file_md5 = $this->mod_md5($mod,$version);
				$pfile_md5 = empty($file_md5) ? "Null" : $file_md5;
			}

			$ver = new Modversion();
			$ver->mod_id = $mod->id;
			$ver->md5 = $md5;
			$ver->version = $version;

			if ($md5 == $file_md5 && !empty($md5)) {
				$ver->md5 = $md5;
				$ver->save();
				return Response::json(array(
							'status' => 'success',
							'version' => $ver->version,
							'md5' => $ver->md5,
							));
			} else if ($md5 != $file_md5 && !empty($md5)) {
				$ver->md5 = $md5;
				$ver->save();
				return Response::json(array(
							'status' => 'warning',
							'version' => $ver->version,
							'md5' => $ver->md5,
							'reason' => 'MD5 provided does not match file MD5: ' . $pfile_md5
							));
			} else {
				return Response::json(array(
							'status' => 'error',
							'reason' => 'Remote MD5 failed. See app/storage/logs for more details'
							));
			}
		}

		return Response::view('errors.missing', array(), 404);
	}

	public function anyDeleteVersion($ver_id = null)
	{
		if (Request::ajax())
		{
			if (empty($ver_id))
				return Response::json(array(
							'status' => 'error',
							'reason' => 'Missing Post Data'
							));

			$ver = Modversion::find($ver_id);
			if (empty($ver))
				return Response::json(array(
							'status' => 'error',
							'reason' => 'Could not pull mod version from database'
							));

			$old_id = $ver->id;
			$old_version = $ver->version;
			$ver->delete();
			return Response::json(array(
									'status' => 'success',
									'version' => $old_version,
									'version_id' => $old_id
									));
		}

		return Response::view('errors.missing', array(), 404);
	}

	private function mod_md5($mod, $version)
	{
		$location = Config::get('solder.repo_location').'mods/'.$mod->name.'/'.$mod->name.'-'.$version.'.zip';

		if (file_exists($location)) {
			Log::info('Found \'' . $location . '\'');
			return md5_file($location);
		} else {
			Log::warning('File \'' . $location . '\' was not found.');
			return $this->remote_mod_md5($mod, $version);
		}
	}

	private function remote_mod_md5($mod, $version, $attempts = 0)
	{
		$url = Config::get('solder.repo_location').'mods/'.$mod->name.'/'.$mod->name.'-'.$version.'.zip';
		if ($attempts >= 3)
		{
			Log::error("Exceeded maximum number of attempts for remote MD5 on mod ". $mod->name ." version ".$version." located at ". $url);
			return "";
		}

		$hash = UrlUtils::get_remote_md5($url);

		if ($hash != "")
			return $hash;
		else {
			Log::warning("Attempted to remote MD5 mod " . $mod->name . " version " . $version . " located at " . $url ." but curl response did not return 200!");
			return $this->remote_mod_md5($mod, $version, $attempts + 1);
		}
	}
}

Validator::extend('csv', function($attribute, $value, $parameters)
{
	return (false !== strpos($value->getClientOriginalName(), '.csv') && 'application/octet-stream' === $value->getClientMimeType());
});