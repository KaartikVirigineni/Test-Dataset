using Microsoft.AspNetCore.Mvc;

namespace DevPortal.Controllers;

[ApiController]
public class SwaggerController : ControllerBase
{
    [HttpGet("/swagger")]
    public IActionResult GetSwaggerSpec()
    {
        var filePath = Path.Combine(Directory.GetCurrentDirectory(), "openapi.yaml");
        
        if (!System.IO.File.Exists(filePath))
        {
            return NotFound();
        }

        var yaml = System.IO.File.ReadAllText(filePath);
        return Content(yaml, "application/yaml");
    }
}