<?php namespace app\components\proxyService\streams;

use Laminas\Diactoros\Stream;

use function stream_get_contents;

/**
 * Caching version of php://input
 */
class CustomPhpInputStream extends Stream
{
    protected $additionalParams = [];


    /**
     * @param  string|resource $stream
     */
    public function __construct($stream = 'php://input')
    {
        parent::__construct($stream, 'r');
    }

    /**
     * {@inheritdoc}
     */
    public function __toString() : string
    {
        return $this->getContents();
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable() : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents($maxLength = -1) : string
    {
        $contents = stream_get_contents($this->resource, $maxLength);
        $contents = $this->addAdditionalContent($contents);

        return $contents;
    }

    /**
     * @param array $params
     */
    public function setAdditionalParams(array $params)
    {
        $this->additionalParams = $params;
    }

    /**
     * @return array
     */
    public function getAdditionalParams()
    {
        return $this->additionalParams;
    }

    /**
     * @param string $contents
     * @return string
     */
    protected function addAdditionalContent($contents)
    {
        if (!empty($this->additionalParams)) {
            $additionalContent = http_build_query($this->additionalParams);
            if (!empty($contents)) {
                $contents .= '&' . $additionalContent;
            } else {
                $contents = $additionalContent;
            }
        }
        return $contents;
    }
}