<?php

namespace Voco\Helpers;

use Image;

class VocoThumb
{
    private $zoom_crop = false;
    private $width = null;
    private $height = null;
    // cache lifetime in minutes
    private $lifetime = 60;
    private $default_image = null;

    /* intervention properties */
    private $greyscale = false;

    public function setLifetime($minute)
    {
        $this->lifetime = $minute;

        return $this;
    }

    public function setDefaultImage($image)
    {
        $this->default_image = $image;

        return $this;
    }

    public function greyscale()
    {
        $this->greyscale = true;

        return $this;
    }

    public function thumb($path, $filename, $width = null, $height = null, $zc = null)
    {
        $this->zoom_crop = ($zc === true || $zc == 1);

        if (empty($width) && empty($height)) {
            $this->width = 50;
            $this->height = 50;
        } else {
            $this->width = $width;
            $this->height = $height;
        }

        return $this->getImage($path, $filename)->response();
    }

    // -------------------------------------------------------------------------
    // Private functions
    // -------------------------------------------------------------------------

    private function getImage($path, $filename)
    {
        $path = trim($path, '/');
        $filename = trim($filename, '/');

        if (!empty($path) && !empty($filename) && file_exists($path . '/' . $filename)) {
            return $this->resizeImage($path . '/' . $filename);
        }

        if ($this->default_image !== null) {
            return $this->resizeImage($this->default_image);
        }

        return $this->onePixelImage();
    }

    private function resizeImage($full_path)
    {
        return Image::cache(function ($img) use ($full_path) {

                    // create Image from file
                    $img->make($full_path);

                    // apply resize criteria
                    $this->applyResize($img);
                }, $this->lifetime, true);
    }

    private function applyResize($img)
    {
        $resize_width = $this->width ? $this->width : null;
        $resize_height = $this->height ? $this->height : null;

        if ($this->greyscale) {
            $img->greyscale();
        }

        if ($this->zoom_crop && $resize_width && $resize_height) {

            // fit to preferred size
            $img->fit($resize_width, $resize_height, function ($constraint) {
                $constraint->aspectRatio();
            });

            return;
        }

        // resize image while keeping aspect ratio
        $img->resize($resize_width, $resize_height, function ($constraint) {
            $constraint->upsize();
            $constraint->aspectRatio();
        });
    }

    private function onePixelImage()
    {
        return Image::cache(function ($img) {
                    // create a new image resource from binary data
                    $img->make(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNiOAMAANUAz5n+TlUAAAAASUVORK5CYII='));

                    if ($this->greyscale) {
                        $img->greyscale();
                    }

                    $img->fit($this->width, $this->height, function ($constraint) {
                        if (false === $this->zoom_crop) {
                            $constraint->aspectRatio();
                        }
                    });
                }, $this->lifetime, true);
    }

}
