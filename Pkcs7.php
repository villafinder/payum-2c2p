<?php

namespace Villafinder\Payum2c2p;

/**
 * From https://developer.2c2p.com/docs/read-payment-response-1
 */
class Pkcs7
{
    /**
     * @param string $text
     * @param string $public
     * @param string $private
     * @param string $password
     * @return string
     * @throws \Exception
     */
    function decrypt($text, $public, $private, $password)
    {
        $arr = str_split($text, 64);
        $text = "";
        foreach ($arr as $val) {
            $text .= $val . "\n";
        }

        $text = "MIME-Version: 1.0
Content-Disposition: attachment; filename=\"smime.p7m\"
Content-Type: application/pkcs7-mime; smime-type=enveloped-data; name=\"smime.p7m\"
Content-Transfer-Encoding: base64

" . $text;

        $text = rtrim($text, "\n");

        $tmpDir = sys_get_temp_dir();
        if (!in_array(substr($tmpDir, 0, -1), array('/', '\\'))) {
            $tmpDir .= '/';
        }

        $fileName = uniqid(time());

        $infilename = $tmpDir.$fileName.".txt";
        file_put_contents($infilename, $text);

        $outfilename = $tmpDir.$fileName.".dec";
        if (openssl_pkcs7_decrypt($infilename, $outfilename, $public, array($private, $password))) {
            $content = file_get_contents($outfilename);

            unlink($infilename);
            unlink($outfilename);

            return $content;
        } else {
            unlink($outfilename);
            unlink($infilename);

            throw new \Exception('Decrypt failed.');
        }
    }
}
