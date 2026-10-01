FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY LogViewLite.sln ./
COPY LogViewLite/LogViewLite.csproj LogViewLite/
RUN dotnet restore

COPY . .
WORKDIR /src/LogViewLite
RUN dotnet build -c Release -o /app/build

FROM build AS publish
RUN dotnet publish -c Release -o /app/publish /p:UseAppHost=false

FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS final
WORKDIR /app
EXPOSE 8080
COPY --from=publish /app/publish .
COPY openapi.yaml .
ENTRYPOINT ["dotnet", "LogViewLite.dll"]