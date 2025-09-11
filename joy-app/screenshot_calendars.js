import { chromium } from 'playwright';

async function takeScreenshots() {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  try {
    // Set viewport to ensure consistent screenshots
    await page.setViewportSize({ width: 1200, height: 800 });
    
    console.log('Taking screenshot of playground calendar...');
    // Navigate to the local HTML file
    const htmlFilePath = 'file:///Users/jprevel/Documents/joy/playground/calendar.html';
    await page.goto(htmlFilePath);
    
    // Wait a moment for any potential JavaScript to load
    await page.waitForTimeout(2000);
    
    // Take full page screenshot of the playground version
    await page.screenshot({ 
      path: '/Users/jprevel/Documents/plotwist/plotwist-app/playground-calendar-original.png',
      fullPage: true 
    });
    console.log('Playground calendar screenshot saved');
    
    console.log('Taking screenshot of Laravel implementation...');
    // Navigate to the Laravel implementation
    await page.goto('http://localhost:8000/calendar');
    
    // Wait a moment for the page to load completely
    await page.waitForTimeout(2000);
    
    // Take full page screenshot of the Laravel version
    await page.screenshot({ 
      path: '/Users/jprevel/Documents/plotwist/plotwist-app/laravel-calendar-implementation.png',
      fullPage: true 
    });
    console.log('Laravel calendar screenshot saved');
    
  } catch (error) {
    console.error('Error taking screenshots:', error);
  } finally {
    await browser.close();
  }
  
  console.log('Screenshots completed!');
  console.log('Files saved:');
  console.log('- /Users/jprevel/Documents/plotwist/plotwist-app/playground-calendar-original.png');
  console.log('- /Users/jprevel/Documents/plotwist/plotwist-app/laravel-calendar-implementation.png');
}

takeScreenshots();