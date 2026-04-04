<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';

class ResearchPermitLetterModel extends BaseModel
{
    private const REQUIRED_FIELDS = [
        'research_title',
        'research_location',
        'start_date',
        'end_date',
        'researcher_name',
        'institution',
        'supervisor',
        'research_scheme',
        'funding_source',
        'research_year',
        'phone',
        'unit',
        'faculty',
        'purpose',
        'destination_position',
        'address',
        'city',
        'attachment_file',
        'notes',
        'applicant_email',
        'members',
    ];

    public function create(array $data): int
    {
        $this->requireKeys($data, array_merge(['letter_id'], self::REQUIRED_FIELDS), 'Create research permit');
        $payload = $this->normalizePayload($data);

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO research_permit_letters
            (letter_id, research_title, research_location, start_date, end_date, researcher_name, institution, supervisor, research_scheme, funding_source, research_year, phone, unit, faculty, purpose, destination_position, address, city, attachment_file, notes, applicant_email, members)
            VALUES
            (:letter_id, :research_title, :research_location, :start_date, :end_date, :researcher_name, :institution, :supervisor, :research_scheme, :funding_source, :research_year, :phone, :unit, :faculty, :purpose, :destination_position, :address, :city, :attachment_file, :notes, :applicant_email, :members)'
        );
        $stmt->execute($payload);

        return (int) $pdo->lastInsertId();
    }

    public function updateByLetterId(int $letterId, array $data): void
    {
        $this->requireKeys($data, self::REQUIRED_FIELDS, 'Update research permit');
        $payload = $this->normalizePayload($data);
        $payload[':letter_id'] = $letterId;

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'UPDATE research_permit_letters
             SET research_title = :research_title,
                 research_location = :research_location,
                 start_date = :start_date,
                 end_date = :end_date,
                 researcher_name = :researcher_name,
                 institution = :institution,
                 supervisor = :supervisor,
                 research_scheme = :research_scheme,
                 funding_source = :funding_source,
                 research_year = :research_year,
                 phone = :phone,
                 unit = :unit,
                 faculty = :faculty,
                 purpose = :purpose,
                 destination_position = :destination_position,
                 address = :address,
                 city = :city,
                 attachment_file = :attachment_file,
                 notes = :notes,
                 applicant_email = :applicant_email,
                 members = :members
             WHERE letter_id = :letter_id'
        );
        $stmt->execute($payload);
    }

    public function findByLetterId(int $letterId): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('SELECT * FROM research_permit_letters WHERE letter_id = :letter_id LIMIT 1');
        $stmt->execute([':letter_id' => $letterId]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    private function normalizePayload(array $data): array
    {
        return [
            ':letter_id' => $this->readInt($data, 'letter_id'),
            ':research_title' => $this->readString($data, 'research_title'),
            ':research_location' => $this->readString($data, 'research_location'),
            ':start_date' => $this->readString($data, 'start_date'),
            ':end_date' => $this->readString($data, 'end_date'),
            ':researcher_name' => $this->readString($data, 'researcher_name'),
            ':institution' => $this->readString($data, 'institution'),
            ':supervisor' => $this->readString($data, 'supervisor'),
            ':research_scheme' => $this->readString($data, 'research_scheme'),
            ':funding_source' => $this->readString($data, 'funding_source'),
            ':research_year' => $this->readString($data, 'research_year'),
            ':phone' => $this->readString($data, 'phone'),
            ':unit' => $this->readString($data, 'unit'),
            ':faculty' => $this->readString($data, 'faculty'),
            ':purpose' => $this->readString($data, 'purpose'),
            ':destination_position' => $this->readString($data, 'destination_position'),
            ':address' => $this->readString($data, 'address'),
            ':city' => $this->readString($data, 'city'),
            ':attachment_file' => $this->readString($data, 'attachment_file'),
            ':notes' => $this->readString($data, 'notes'),
            ':applicant_email' => $this->readString($data, 'applicant_email'),
            ':members' => $this->readString($data, 'members'),
        ];
    }
}
