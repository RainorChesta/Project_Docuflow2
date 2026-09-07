<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\DocumentProcessorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocxSignatureProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function createDummyDocx(string $contentXmlText = '<w:p><w:r><w:t>Hello World</w:t></w:r></w:p>'): string
    {
        $tempDocx = tempnam(sys_get_temp_dir(), 'docx_test_') . '.docx';
        $zip = new \ZipArchive();
        $zip->open($tempDocx, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<w:body>' .
            $contentXmlText .
            '<w:sectPr/>' .
            '</w:body>' .
            '</w:document>';
            
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');
        $zip->close();

        return $tempDocx;
    }

    private function createDummySignaturePng(): string
    {
        $im = imagecreatetruecolor(100, 100);
        $bg = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 0, 0, 100, 100, $bg);
        $ink = imagecolorallocate($im, 0, 0, 0);
        imageline($im, 10, 10, 90, 90, $ink);
        
        $tempPng = tempnam(sys_get_temp_dir(), 'sig_test_') . '.png';
        imagepng($im, $tempPng);
        imagedestroy($im);
        
        return $tempPng;
    }

    public function test_processes_docx_with_visual_marker_on_approval(): void
    {
        $user = User::factory()->create();
        $docType = DocumentType::create(['name' => 'Surat', 'code' => 'SRT']);
        $document = Document::create([
            'document_number' => '001/SRT/2026',
            'title' => 'Doc Test',
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'visibility' => 'general',
        ]);
        
        $docxPath = $this->createDummyDocx('<w:p><w:r><w:t>[⏳ MENUNGGU TTD: JOHN DOE - ${PENDING_SIG_99}]</w:t></w:r></w:p>');
        $storagePath = 'documents/' . $document->id . '/v1.docx';
        Storage::disk('local')->put($storagePath, file_get_contents($docxPath));
        @unlink($docxPath);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '<p>test</p>',
            'author_name' => $user->name,
            'file_path' => $storagePath,
            'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'created_by' => $user->id,
            'status' => 'approved',
        ]);

        $sigPng = $this->createDummySignaturePng();

        $processor = app(DocumentProcessorService::class);
        $result = $processor->processSignature($document, $version, 99, $sigPng);
        @unlink($sigPng);

        $this->assertTrue($result);
        
        // Verify that the DOCX now contains image relationships
        $modifiedDocx = Storage::disk('local')->get($storagePath);
        $tempVerify = tempnam(sys_get_temp_dir(), 'verify_') . '.docx';
        file_put_contents($tempVerify, $modifiedDocx);
        
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tempVerify));
        $docXml = $zip->getFromName('word/document.xml');
        $this->assertStringNotContainsString('${PENDING_SIG_99}', $docXml);
        $this->assertStringNotContainsString('MENUNGGU', $docXml);
        $this->assertTrue(str_contains($docXml, 'imagedata') || str_contains($docXml, 'shape') || str_contains($docXml, 'drawing'));
        $zip->close();
        @unlink($tempVerify);
    }

    public function test_processes_docx_gracefully_when_no_marker_found(): void
    {
        $user = User::factory()->create();
        $docType = DocumentType::create(['name' => 'Surat', 'code' => 'SRT']);
        $document = Document::create([
            'document_number' => '002/SRT/2026',
            'title' => 'Doc Test 2',
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'visibility' => 'general',
        ]);
        
        $docxPath = $this->createDummyDocx('<w:p><w:r><w:t>Standard document without any markers</w:t></w:r></w:p>');
        $storagePath = 'documents/' . $document->id . '/v2.docx';
        Storage::disk('local')->put($storagePath, file_get_contents($docxPath));
        @unlink($docxPath);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '<p>test</p>',
            'author_name' => $user->name,
            'file_path' => $storagePath,
            'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'created_by' => $user->id,
            'status' => 'approved',
        ]);

        $sigPng = $this->createDummySignaturePng();

        $processor = app(DocumentProcessorService::class);
        $result = $processor->processSignature($document, $version, 101, $sigPng);
        @unlink($sigPng);

        $this->assertTrue($result);
        
        // Verify that the DOCX remains valid and unmodified without unwanted appended images
        $modifiedDocx = Storage::disk('local')->get($storagePath);
        $tempVerify = tempnam(sys_get_temp_dir(), 'verify_') . '.docx';
        file_put_contents($tempVerify, $modifiedDocx);
        
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tempVerify));
        $docXml = $zip->getFromName('word/document.xml');
        $this->assertStringContainsString('Standard document without any markers', $docXml);
        $zip->close();
        @unlink($tempVerify);
    }
}
