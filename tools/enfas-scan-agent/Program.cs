using NAPS2.Images;
using NAPS2.Images.Gdi;
using NAPS2.Pdf;
using NAPS2.Scan;

var builder = WebApplication.CreateBuilder(args);

builder.WebHost.UseUrls("http://127.0.0.1:19876");

builder.Services.AddCors(options =>
{
    options.AddDefaultPolicy(policy =>
    {
        policy
            .WithOrigins(
                "https://agenda.enfas.com.br",
                "http://localhost",
                "http://127.0.0.1"
            )
            .AllowAnyHeader()
            .AllowAnyMethod();
    });
});

var app = builder.Build();
app.UseCors();

app.MapGet("/health", () => Results.Ok(new
{
    ok = true,
    product = "ENFAS Scan Agent",
    version = "0.1.0"
}));

app.MapGet("/devices", async (string? driver) =>
{
    using var context = new ScanningContext(new GdiImageContext());
    context.SetUpWin32Worker();

    var controller = new ScanController(context);
    var selectedDriver = ParseDriver(driver);
    var devices = await controller.GetDeviceList(selectedDriver);

    return Results.Ok(new
    {
        devices = devices.Select(device => new
        {
            id = device.ID,
            name = device.Name,
            driver = device.Driver.ToString().ToLowerInvariant()
        })
    });
});

app.MapPost("/scan", async (ScanRequest request) =>
{
    using var context = new ScanningContext(new GdiImageContext());
    context.SetUpWin32Worker();

    var controller = new ScanController(context);
    var driver = ParseDriver(request.Driver);
    var devices = await controller.GetDeviceList(driver);
    var device = devices.FirstOrDefault(d =>
        string.Equals(d.ID, request.Device, StringComparison.OrdinalIgnoreCase)
        || string.Equals(d.Name, request.Device, StringComparison.OrdinalIgnoreCase));

    if (device is null)
    {
        return Results.NotFound(new { error = "Scanner não encontrado." });
    }

    var options = new ScanOptions
    {
        Device = device,
        Driver = driver,
        PaperSource = ParseSource(request.Source),
        PageSize = PageSize.A4,
        Dpi = request.Dpi is >= 100 and <= 1200 ? request.Dpi : 300,
        BitDepth = ParseBitDepth(request.Color),
        UseNativeUI = false
    };

    var images = await controller.Scan(options).ToListAsync();

    if (images.Count == 0)
    {
        return Results.BadRequest(new { error = "Nenhuma página foi digitalizada." });
    }

    var temp = Path.Combine(Path.GetTempPath(), $"enfas-scan-{Guid.NewGuid():N}.pdf");

    try
    {
        var exporter = new PdfExporter(context);
        await exporter.Export(temp, images);
        var bytes = await File.ReadAllBytesAsync(temp);

        return Results.File(
            bytes,
            "application/pdf",
            $"digitalizacao-{DateTime.Now:yyyyMMdd-HHmmss}.pdf"
        );
    }
    finally
    {
        foreach (var image in images)
        {
            image.Dispose();
        }

        if (File.Exists(temp))
        {
            File.Delete(temp);
        }
    }
});

app.Run();

static Driver ParseDriver(string? value) => (value ?? "").Trim().ToLowerInvariant() switch
{
    "twain" => Driver.Twain,
    "escl" => Driver.Escl,
    _ => Driver.Wia
};

static PaperSource ParseSource(string? value) => (value ?? "").Trim().ToLowerInvariant() switch
{
    "glass" => PaperSource.Flatbed,
    "duplex" => PaperSource.Duplex,
    _ => PaperSource.Feeder
};

static BitDepth ParseBitDepth(string? value) => (value ?? "").Trim().ToLowerInvariant() switch
{
    "bw" => BitDepth.BlackAndWhite,
    "gray" => BitDepth.Grayscale,
    _ => BitDepth.Color
};

public sealed record ScanRequest(
    string? Driver,
    string Device,
    string? Source,
    int Dpi,
    string? Color
);
