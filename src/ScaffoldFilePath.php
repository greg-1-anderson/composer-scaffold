<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold;

use Grasmash\ComposerScaffold\Interpolator;

/**
 * Manage the path to a file to scaffold.
 *
 * Both the relative and full path to the file is maintained so that the
 * shorter name may be used in progress and error messages, as needed.
 * The name of the package that provided the file path is also recorded
 * for the same reason.
 *
 * ScaffoldFilePaths may be used to represent destination scaffold files,
 * or the source files used to create them. Static factory methods named
 * destinationPath and sourcePath, respectively, are provided to create
 * ScafoldFilePath objects.
 */
class ScaffoldFilePath {

  protected $type;
  protected $packageName;
  protected $relPath;
  protected $fullPath;

  /**
   * ScaffoldFilePath constructor.
   *
   * @param string $path_type
   *   The type of scaffold file this is, 'src' or 'dest'.
   * @param string $package_name
   *   The name of the package containing the file.
   * @param string $rel_path
   *   The relative path to the file.
   * @param string $full_path
   *   The full path to the file.
   */
  public function __construct(string $path_type, string $package_name, string $rel_path, string $full_path) {
    $this->type = $path_type;
    $this->packageName = $package_name;
    $this->relPath = $rel_path;
    $this->fullPath = $full_path;
  }

  /**
   * The name of the package this source file was pulled from.
   *
   * @return string
   *   Name of package.
   */
  public function packageName() : string {
    return $this->packageName;
  }

  /**
   * The relative path to the source file (best to use in messages).
   *
   * @return string
   *   Relative path to file.
   */
  public function relativePath() : string {
    return $this->relPath;
  }

  /**
   * The full path to the source file.
   *
   * @return string
   *   Full path to file.
   */
  public function fullPath() : string {
    return $this->fullPath;
  }

  /**
   * Convert the relative source path into an absolute path.
   *
   * The path returned will be relative to the package installation location.
   *
   * @param string $package_name
   *   The name of the package containing the source file. Only used for error messages.
   * @param string $package_path
   *   The installation path of the package containing the source file.
   * @param string $destination
   *   Destination location provided as a relative path. Only used for error messages.
   * @param string $source
   *   Source location provided as a relative path.
   *
   * @return self
   *   Object wrapping the relative and absolute path to the source file.
   */
  public static function sourcePath(string $package_name, string $package_path, string $destination, string $source) : self {
    // Complain if there is no source path.
    if (empty($source)) {
      throw new \Exception("No scaffold file path given for $destination in package $package_name.");
    }

    // Calculate the full path to the source scaffold file.
    $source_full_path = $package_path . '/' . $source;

    if (!file_exists($source_full_path)) {
      throw new \Exception("Scaffold file $source not found in package $package_name.");
    }
    if (is_dir($source_full_path)) {
      throw new \Exception("Scaffold file $source in package $package_name is a directory; only files may be scaffolded.");
    }

    return new self('src', $package_name, $source, $source_full_path);
  }

  /**
   * Convert the relative destination path into an absolute path.
   *
   * Any placeholders in the destination path, e.g. '[web-root]', will be
   * replaced using the provided location replacements interpolator.
   *
   * @param string $package_name
   *   The name of the package defining the destination path.
   * @param string $destination
   *   The relative path to the destination file being scaffolded.
   * @param \Grasmash\ComposerScaffold\Interpolator $locationReplacements
   *   Interpolator that includes the [web-root] and any other available
   *   placeholder replacements.
   *
   * @return self
   *   Object wrapping the relative and absolute path to the destination file.
   */
  public static function destinationPath(string $package_name, string $destination, Interpolator $locationReplacements) : self {
    $dest_full_path = $locationReplacements->interpolate($destination);

    return new self('dest', $package_name, $destination, $dest_full_path);
  }

  /**
   * Generate a scaffold file path object for the autoload file.
   *
   * @param string $package_name
   *   The name of the package defining the autoload file (the root package).
   * @param string $web_root
   *   The path to the web root.
   *
   * @return self
   *   Object wrapping the relative and absolute path to the destination file.
   */
  public static function autoloadPath(string $package_name, string $web_root) : self {
    $rel_path = 'autoload.php';
    $dest_rel_path = '[web-root]/' . $rel_path;
    $dest_full_path = $web_root . '/' . $rel_path;
    return new self('autoload', $package_name, $dest_rel_path, $dest_full_path);
  }

  /**
   * Add data about the relative and full path to this item to the provided interpolator.
   *
   * @param \Grasmash\ComposerScaffold\Interpolator $interpolator
   *   Interpolator to add data to.
   * @param string $namePrefix
   *   Prefix to add before -rel-path and -full-path item names. Defaults to path type.
   */
  public function addInterpolationData(Interpolator $interpolator, string $namePrefix = '') {
    if (empty($namePrefix)) {
      $namePrefix = $this->type;
    }
    $data = [
      'package-name' => $this->packageName(),
      "{$namePrefix}-rel-path" => $this->relativePath(),
      "{$namePrefix}-full-path" => $this->fullPath(),
    ];
    $interpolator->addData($data);
  }

  /**
   * Interpolate a string using the data from this scaffold file info.
   *
   * @param string $namePrefix
   *   Prefix to add before -rel-path and -full-path item names. Defaults to path type.
   *
   * @return \Grasmash\ComposerScaffold\Interpolator
   *   An interpolator for making string replacements.
   */
  public function getInterpolator($namePrefix = '') : Interpolator {
    $interpolator = new Interpolator();
    $this->addInterpolationData($interpolator, $namePrefix);
    return $interpolator;
  }

}
