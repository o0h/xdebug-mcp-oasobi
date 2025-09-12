<?php

namespace App\Formatter;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class WeatherFormatter
{
    private array $iconMap = [
        '01d' => '☀️',
        '01n' => '🌙',
        '02d' => '⛅',
        '02n' => '☁️',
        '03d' => '☁️',
        '03n' => '☁️',
        '04d' => '☁️',
        '04n' => '☁️',
        '09d' => '🌧️',
        '09n' => '🌧️',
        '10d' => '🌦️',
        '10n' => '🌧️',
        '11d' => '⛈️',
        '11n' => '⛈️',
        '13d' => '❄️',
        '13n' => '❄️',
        '50d' => '🌫️',
        '50n' => '🌫️',
    ];
    
    public function formatCurrent(array $data, OutputInterface $output, string $format = 'table'): void
    {
        if ($format === 'json') {
            $output->writeln(json_encode($data, JSON_PRETTY_PRINT));
            return;
        }
        
        if ($format === 'simple') {
            $this->formatSimple($data, $output);
            return;
        }
        
        $this->formatTable($data, $output);
    }
    
    private function formatSimple(array $data, OutputInterface $output): void
    {
        $city = $data['name'] ?? 'Unknown';
        $country = $data['sys']['country'] ?? '';
        $temp = $data['main']['temp'] ?? 0;
        $feels = $data['main']['feels_like'] ?? 0;
        $description = $data['weather'][0]['description'] ?? 'Unknown';
        $icon = $data['weather'][0]['icon'] ?? '';
        
        $emoji = $this->iconMap[$icon] ?? '🌡️';
        
        $output->writeln("");
        $output->writeln(sprintf("  %s <info>%s, %s</info>", $emoji, $city, $country));
        $output->writeln(sprintf("  Temperature: <comment>%.1f°C</comment> (feels like %.1f°C)", $temp, $feels));
        $output->writeln(sprintf("  Conditions: %s", ucfirst($description)));
        $output->writeln("");
    }
    
    private function formatTable(array $data, OutputInterface $output): void
    {
        $city = $data['name'] ?? 'Unknown';
        $country = $data['sys']['country'] ?? '';
        
        $output->writeln("");
        $output->writeln(sprintf("<info>Weather for %s, %s</info>", $city, $country));
        $output->writeln("");
        
        $table = new Table($output);
        $table->setHeaders(['Property', 'Value']);
        
        $main = $data['main'];
        $weather = $data['weather'][0];
        $wind = $data['wind'] ?? [];
        $clouds = $data['clouds'] ?? [];
        
        $rows = [
            ['Conditions', ucfirst($weather['description'] ?? 'Unknown')],
            ['Temperature', sprintf('%.1f°C', $main['temp'] ?? 0)],
            ['Feels Like', sprintf('%.1f°C', $main['feels_like'] ?? 0)],
            ['Min/Max', sprintf('%.1f°C / %.1f°C', $main['temp_min'] ?? 0, $main['temp_max'] ?? 0)],
            ['Humidity', sprintf('%d%%', $main['humidity'] ?? 0)],
            ['Pressure', sprintf('%d hPa', $main['pressure'] ?? 0)],
            ['Wind Speed', sprintf('%.1f m/s', $wind['speed'] ?? 0)],
            ['Wind Direction', sprintf('%d°', $wind['deg'] ?? 0)],
            ['Cloudiness', sprintf('%d%%', $clouds['all'] ?? 0)],
        ];
        
        if (isset($data['visibility'])) {
            $rows[] = ['Visibility', sprintf('%.1f km', $data['visibility'] / 1000)];
        }
        
        $table->setRows($rows);
        $table->render();
        $output->writeln("");
    }
    
    public function formatForecast(array $data, OutputInterface $output, string $format = 'table', int $days = 5): void
    {
        if ($format === 'json') {
            $output->writeln(json_encode($data, JSON_PRETTY_PRINT));
            return;
        }
        
        $city = $data['city']['name'] ?? 'Unknown';
        $country = $data['city']['country'] ?? '';
        
        $output->writeln("");
        $output->writeln(sprintf("<info>%d-Day Forecast for %s, %s</info>", $days, $city, $country));
        $output->writeln("");
        
        $table = new Table($output);
        $table->setHeaders(['Date/Time', 'Temp', 'Feels Like', 'Conditions', 'Humidity', 'Wind']);
        
        $rows = [];
        $count = 0;
        
        foreach ($data['list'] as $item) {
            if ($count >= $days * 8) {
                break;
            }
            
            $dt = date('Y-m-d H:i', $item['dt']);
            $temp = sprintf('%.1f°C', $item['main']['temp']);
            $feels = sprintf('%.1f°C', $item['main']['feels_like']);
            $conditions = ucfirst($item['weather'][0]['description']);
            $humidity = sprintf('%d%%', $item['main']['humidity']);
            $wind = sprintf('%.1f m/s', $item['wind']['speed']);
            
            $rows[] = [$dt, $temp, $feels, $conditions, $humidity, $wind];
            $count++;
        }
        
        $table->setRows($rows);
        $table->render();
        $output->writeln("");
    }
}