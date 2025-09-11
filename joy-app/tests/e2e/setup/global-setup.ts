import { chromium, FullConfig } from '@playwright/test';
import { exec } from 'child_process';
import { promisify } from 'util';

const execAsync = promisify(exec);

async function globalSetup(config: FullConfig) {
  console.log('Running global setup...');
  
  try {
    // Refresh database with fresh migrations and seeding
    console.log('Refreshing database with seeding...');
    await execAsync('php artisan migrate:fresh --seed --force');
    
    // Clear any application cache
    console.log('Clearing application cache...');
    await execAsync('php artisan cache:clear');
    await execAsync('php artisan config:clear');
    
    console.log('Global setup completed successfully');
    
  } catch (error) {
    console.error('Global setup failed:', error);
    throw error;
  }
}

export default globalSetup;