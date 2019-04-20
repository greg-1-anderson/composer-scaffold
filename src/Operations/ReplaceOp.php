<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * Scaffold operation to copy or symlink from source to destination.
 */
class ReplaceOp implements OperationInterface {

  protected $source;
  protected $overwrite;

  /**
   * Set the relative path to the source.
   *
   * @param ScaffoldSourcePath $sourcePath
   *   The relative path to the source file.
   *
   * @return $this
   */
  public function setSource(ScaffoldSourcePath $sourcePath) {
    $this->source = $sourcePath;
    return $this;
  }

  /**
   * Get the source.
   *
   * @return ScaffoldSourcePath
   *   The source file reference object.
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Set whether the scaffold file should overwrite existing files at the same path.
   *
   * @param bool $overwrite
   *   Whether to overwrite existing files.
   *
   * @return $this
   */
  public function setOverwrite(bool $overwrite) {
    $this->overwrite = $overwrite;
    return $this;
  }

  /**
   * Determine whether scaffold file should overwrite files already at the same path.
   *
   * @return bool
   *   Value of the 'overwrite' option.
   */
  public function getOverwrite() {
    return $this->overwrite;
  }

  /**
   * Interpolate a string using the data from this scaffold file info.
   */
  public function interpolationData() {
    return [
      'src-rel-path' => $this->getSource()->relativePath(),
      'src-full-path' => $this->getSource()->fullPath(),
    ];
    return $data;
  }

  /**
   * Process the replace operation. This could be a copy or a symlink.
   */
  public function process(ScaffoldFileInfo $scaffold_file, IOInterface $io, array $options) {
    $fs = new Filesystem();

    $destination_path = $scaffold_file->getDestinationFullPath();

    // Do nothing if overwrite is 'false' and a file already exists at the destination.
    if (($this->getOverwrite() === FALSE) && file_exists($destination_path)) {
      $interpolator = $scaffold_file->getInterpolator();
      $io->write($interpolator->interpolate("  - Skip scaffold file <info>[dest-rel-path]</info> because it already exists."));
      return;
    }

    // Get rid of the destination if it exists, and make sure that
    // the directory where it's going to be placed exists.
    @unlink($destination_path);
    $fs->ensureDirectoryExists(dirname($destination_path));

    if ($options['symlink'] == TRUE) {
      return $this->symlinkScaffold($scaffold_file, $io, $options);
    }
    return $this->copyScaffold($scaffold_file, $io, $options);
  }

  /**
   * Copy the scaffold file.
   */
  public function copyScaffold(ScaffoldFileInfo $scaffold_file, IOInterface $io, array $options) {
    $interpolator = $scaffold_file->getInterpolator();
    $source_path = $this->getSource()->fullPath();
    $destination_path = $scaffold_file->getDestinationFullPath();

    $success = copy($source_path, $destination_path);
    if (!$success) {
      throw new \Exception($interpolator->interpolate("Could not copy source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>!", $this->interpolationData()));
    }

    $io->write($interpolator->interpolate("  - Copy <info>[dest-rel-path]</info> from <info>[src-rel-path]</info>", $this->interpolationData()));
  }

  /**
   * Symlink the scaffold file.
   */
  public function symlinkScaffold(ScaffoldFileInfo $scaffold_file, IOInterface $io, array $options) {
    $interpolator = $scaffold_file->getInterpolator();
    $source_path = $this->getSource()->fullPath();
    $destination_path = $scaffold_file->getDestinationFullPath();

    try {
      $fs = new Filesystem();
      $fs->relativeSymlink($source_path, $destination_path);
    }
    catch (\Exception $e) {
      throw new \Exception($interpolator->interpolate("Could not symlink source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>! ", $this->interpolationData()), 1, $e);
    }

    $io->write($interpolator->interpolate("  - Link <info>[dest-rel-path]</info> from <info>[src-rel-path]</info>", $this->interpolationData()));
  }

}
